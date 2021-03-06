<?php

/**
 * Copyright 2006-2010 Jan Kanters
 * Copyright 2010-2014 Gert Ebersbach
 * Copyright 2014-2017 Christoph M. Becker
 *
 * This file is part of Realblog_XH.
 *
 * Realblog_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Realblog_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Realblog_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Realblog;

use stdClass;
use SQLite3;

class DB
{
    /**
     * @var self
     */
    private static $instance;

    /**
     * @var SQLite3
     */
    private $connection;

    /**
     * @return SQLite3
     */
    public static function getConnection()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance->connection;
    }

    /**
     * @global array $pth
     */
    private function __construct()
    {
        global $pth;

        $filename = "{$pth['folder']['content']}realblog/realblog.db";
        try {
            $this->connection = new Sqlite3($filename, SQLITE3_OPEN_READWRITE);
        } catch (\Exception $ex) {
            $dirname = dirname($filename);
            if (!file_exists($dirname)) {
                mkdir($dirname, 0777);
                chmod($dirname, 0777);
            }
            $this->connection = new Sqlite3($filename);
            $this->createDatabase();
        }
        $this->updateDatabase();
    }

    /**
     * @return void
     */
    private function createDatabase()
    {
        $sql = <<<'EOS'
CREATE TABLE articles (
    id  INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    version INTEGER,
    date INTEGER,
    publishing_date INTEGER,
    archiving_date INTEGER,
    status INTEGER CHECK (status BETWEEN 0 AND 2),
    categories TEXT,
    title TEXT,
    teaser TEXT,
    body TEXT,
    feedable INTEGER,
    commentable INTEGER
);
CREATE INDEX status ON articles (status, date, id);
CREATE INDEX feedable ON articles (feedable, date, id);
EOS;
        $this->connection->exec($sql);
        $this->importFlatfile();
    }

    /**
     * @return void
     * @global array $pth
     */
    private function importFlatfile()
    {
        global $pth;

        $filename = "{$pth['folder']['content']}realblog/realblog.txt";
        if (file_exists($filename)) {
            $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $this->connection->exec('BEGIN TRANSACTION');
            $sql = <<<'SQL'
INSERT INTO articles VALUES (
    :id, 1, :date, :publishing_date, :archiving_date, :status,
    :categories, :title, :teaser, :body, :feedable, :commentable
)
SQL;
            $statement = $this->connection->prepare($sql);
            foreach ($lines as $line) {
                $record = explode("\t", $line);
                $status = ($record[4] == 1 || $record[4] == 2) ? $record[4] : 0;
                $categories = array_merge(
                    $this->getAndRemoveCategories($record[7]),
                    $this->getAndRemoveCategories($record[8])
                );
                $categories = implode(',', $categories);
                $statement->bindValue(':id', $record[0], SQLITE3_INTEGER);
                $statement->bindValue(':date', $record[1], SQLITE3_INTEGER);
                $statement->bindValue(':publishing_date', $record[2], SQLITE3_INTEGER);
                $statement->bindValue(':archiving_date', $record[3], SQLITE3_INTEGER);
                $statement->bindValue(':status', $status, SQLITE3_INTEGER);
                $statement->bindValue(':categories', ",$categories,", SQLITE3_TEXT);
                $statement->bindValue(':title', $record[6], SQLITE3_TEXT);
                $statement->bindValue(':teaser', $record[7], SQLITE3_TEXT);
                $statement->bindValue(':body', $record[8], SQLITE3_TEXT);
                $statement->bindValue(':feedable', $record[9], SQLITE3_INTEGER);
                $statement->bindValue(':commentable', $record[10], SQLITE3_INTEGER);
                $statement->execute();
            }
            $this->connection->exec('COMMIT');
        }
    }

    /**
     * @param int &$field
     * @return array
     */
    private function getAndRemoveCategories(&$field)
    {
        $categories = preg_match('/{{{rbCat\(([^\)]*)\);?}}}/', $field, $matches);
        $categories = explode('|', trim($matches[1], "'|"));
        $categories = array_map(
            function ($cat) {
                return trim($cat);
            },
            $categories
        );
        $field = preg_replace('/{{{rbCat\([^\)]*\);?}}}/', '', $field);
        return $categories;
    }

    private function updateDatabase()
    {
        $sql = <<<'EOS'
CREATE TABLE IF NOT EXISTS page_views (
    article_id INTEGER NOT NULL,
    timestamp INTEGER NOT NULL
);
EOS;
        $this->connection->exec($sql);
    }

    /**
     * @return int
     */
    public static function insertArticle(stdClass $article)
    {
        $db = self::getConnection();
        $sql = <<<'EOS'
INSERT INTO articles
    VALUES (
        :id, 1, :date, :publishing_date, :archiving_date, :status,
        :categories, :title, :teaser, :body, :feedable, :commentable
    )
EOS;
        $statement = $db->prepare($sql);
        $statement->bindValue(':id', null, SQLITE3_NULL);
        $statement->bindValue(':date', $article->date, SQLITE3_INTEGER);
        $statement->bindValue(':publishing_date', $article->publishing_date, SQLITE3_INTEGER);
        $statement->bindValue(':archiving_date', $article->archiving_date, SQLITE3_INTEGER);
        $statement->bindValue(':status', $article->status, SQLITE3_INTEGER);
        $statement->bindValue(':categories', $article->categories, SQLITE3_TEXT);
        $statement->bindValue(':title', $article->title, SQLITE3_TEXT);
        $statement->bindValue(':teaser', $article->teaser, SQLITE3_TEXT);
        $statement->bindValue(':body', $article->body, SQLITE3_TEXT);
        $statement->bindValue(':feedable', $article->feedable, SQLITE3_INTEGER);
        $statement->bindValue(':commentable', $article->commentable, SQLITE3_INTEGER);
        $res = $statement->execute();
        if ($res) {
            $res = $db->changes();
        }
        return $res;
    }

    /**
     * @return int
     */
    public static function updateArticle(stdClass $article)
    {
        $db = self::getConnection();
        $sql = <<<'EOS'
UPDATE articles
    SET version = version + 1, date = :date, publishing_date = :publishing_date,
        archiving_date = :archiving_date, status = :status,
        categories = :categories, title = :title, teaser = :teaser, body = :body,
        feedable = :feedable, commentable = :commentable
    WHERE id = :id AND version = :version
EOS;
        $statement = $db->prepare($sql);
        $statement->bindValue(':id', $article->id, SQLITE3_INTEGER);
        $statement->bindValue(':version', $article->version, SQLITE3_INTEGER);
        $statement->bindValue(':date', $article->date, SQLITE3_INTEGER);
        $statement->bindValue(':publishing_date', $article->publishing_date, SQLITE3_INTEGER);
        $statement->bindValue(':archiving_date', $article->archiving_date, SQLITE3_INTEGER);
        $statement->bindValue(':status', $article->status, SQLITE3_INTEGER);
        $statement->bindValue(':categories', $article->categories, SQLITE3_TEXT);
        $statement->bindValue(':title', $article->title, SQLITE3_TEXT);
        $statement->bindValue(':teaser', $article->teaser, SQLITE3_TEXT);
        $statement->bindValue(':body', $article->body, SQLITE3_TEXT);
        $statement->bindValue(':feedable', $article->feedable, SQLITE3_INTEGER);
        $statement->bindValue(':commentable', $article->commentable, SQLITE3_INTEGER);
        $res = $statement->execute();
        if ($res) {
            $res = $db->changes();
        }
        return $res;
    }

    /**
     * @param string $field
     * @param int $status
     * @return void
     */
    public static function autoChangeStatus($field, $status)
    {
        $db = self::getConnection();
        $sql = <<<SQL
UPDATE articles SET version = version + 1, status = :status
    WHERE status < :status AND $field <= :date
SQL;
        $statement = $db->prepare($sql);
        $statement->bindValue(':status', $status, SQLITE3_INTEGER);
        $statement->bindValue(':date', strtotime('midnight'), SQLITE3_INTEGER);
        $statement->execute();
    }

    /**
     * @param array<int> $ids
     * @param int $status
     * @return int
     */
    public static function updateStatusOfArticlesWithIds(array $ids, $status)
    {
        $sql = sprintf(
            'UPDATE articles SET version = version + 1, status = :status WHERE id in (%s)',
            implode(',', $ids)
        );
        $db = self::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':status', $status, SQLITE3_INTEGER);
        $res = $stmt->execute();
        if ($res) {
            $res = $db->changes();
        }
        return $res;
    }

    /**
     * @return int
     */
    public static function deleteArticle($article)
    {
        $sql = 'DELETE FROM articles WHERE id = :id AND version = :version';
        $db = self::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $article->id, SQLITE3_INTEGER);
        $stmt->bindValue(':version', $article->version, SQLITE3_INTEGER);
        $res = $stmt->execute();
        if ($res) {
            $res = $db->changes();
        }
        return $res;
    }

    /**
     * @param array<int> $ids
     * @return int
     */
    public static function deleteArticlesWithIds(array $ids)
    {
        $sql = sprintf(
            'DELETE FROM articles WHERE id in (%s)',
            implode(',', $ids)
        );
        $db = self::getConnection();
        $res = $db->exec($sql);
        if ($res) {
            $res = $db->changes();
        }
        return $res;
    }

    /**
     * @param int $articleId
     */
    public static function recordPageView($articleId)
    {
        $sql = 'INSERT INTO page_views VALUES (:article_id, :timestamp)';
        $db = self::getConnection();
        $statement = $db->prepare($sql);
        $statement->bindValue(':article_id', $articleId, SQLITE3_INTEGER);
        $statement->bindValue(':timestamp', time());
        $statement->execute();
    }

    /**
     * @param string $filename
     * @return bool
     */
    public static function exportToCsv($filename)
    {
        if (!($stream = fopen($filename, 'w'))) {
            return false;
        }
        $sql = 'SELECT * FROM articles';
        $db = self::getConnection();
        $statement = $db->prepare($sql);
        $result = $statement->execute();
        while (($record = $result->fetchArray(SQLITE3_NUM)) !== false) {
            $record = array_map('XH_rmws', $record);
            fputs($stream, implode("\t", $record) . "\n");
        }
        fclose($stream);
        return true;
    }

    /**
     * @param string $filename
     * @return bool
     */
    public static function importFromCsv($filename)
    {
        $db = self::getConnection();
        $db->exec('BEGIN TRANSACTION');
        $db->exec('DELETE FROM articles');
        $sql = <<<'EOS'
INSERT INTO articles
    VALUES (
        :id, :version, :date, :publishing_date, :archiving_date, :status,
        :categories, :title, :teaser, :body, :feedable, :commentable
    )
EOS;
        $statement = $db->prepare($sql);
        if (!($stream = fopen($filename, 'r'))) {
            return false;
        }
        while (($record = fgetcsv($stream, 0, "\t")) !== false) {
            $statement->bindValue(':id', $record[0], SQLITE3_INTEGER);
            $statement->bindValue(':version', $record[1], SQLITE3_INTEGER);
            $statement->bindValue(':date', $record[2], SQLITE3_INTEGER);
            $statement->bindValue(':publishing_date', $record[3], SQLITE3_INTEGER);
            $statement->bindValue(':archiving_date', $record[4], SQLITE3_INTEGER);
            $statement->bindValue(':status', $record[5], SQLITE3_INTEGER);
            $statement->bindValue(':categories', $record[6], SQLITE3_TEXT);
            $statement->bindValue(':title', $record[7], SQLITE3_TEXT);
            $statement->bindValue(':teaser', $record[8], SQLITE3_TEXT);
            $statement->bindValue(':body', $record[9], SQLITE3_TEXT);
            $statement->bindValue(':feedable', $record[10], SQLITE3_INTEGER);
            $statement->bindValue(':commentable', $record[11], SQLITE3_INTEGER);
            if (!$statement->execute()) {
                return false;
            }
        }
        fclose($stream);
        $db->exec('COMMIT');
        return true;
    }
}
