<?php

/**
 * @author    Jan Kanters <jan.kanters@telenet.be>
 * @author    Gert Ebersbach <mail@ge-webdesign.de>
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2006-2010 Jan Kanters
 * @copyright 2010-2014 Gert Ebersbach <http://ge-webdesign.de/>
 * @copyright 2014-2016 Christoph M. Becker <http://3-magi.net/>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

namespace Realblog;

class InfoView
{
    /**
     * @return string
     */
    public function render()
    {
        return '<h1>Realblog</h1>'
            . $this->renderLogo()
            . '<p>Version: ' . REALBLOG_VERSION . '</p>'
            . $this->renderCopyright() . $this->renderLicense();
    }

    /**
     * @return string
     * @global array $pth
     * @global array $plugin_tx
     */
    protected function renderLogo()
    {
        global $pth, $plugin_tx;

        return tag(
            'img src="' . $pth['folder']['plugins']
            . 'realblog/realblog.png" class="realblog_logo"'
            . ' alt="' . $plugin_tx['realblog']['alt_logo'] . '"'
        );
    }

    /**
     * @return string
     */
    protected function renderCopyright()
    {
        return '<p>Copyright &copy; 2006-2010 Jan Kanters' . tag('br')
            . 'Copyright &copy; 2010-2014 '
            . '<a href="http://www.ge-webdesign.de/" target="_blank">'
            . 'Gert Ebersbach</a>' . tag('br')
            . 'Copyright &copy; 2014-2016 '
            . '<a href="http://3-magi.net/" target="_blank">'
            . 'Christoph M. Becker</a></p>';
    }

    /**
     * @return string
     */
    protected function renderLicense()
    {
        return <<<EOT
<p class="realblog_license">This program is free software: you can redistribute
it and/or modify it under the terms of the GNU General Public License as
published by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.</p>
<p class="realblog_license">This program is distributed in the hope that it will
be useful, but <em>without any warranty</em>; without even the implied warranty
of <em>merchantability</em> or <em>fitness for a particular purpose</em>. See
the GNU General Public License for more details.</p>
<p class="realblog_license">You should have received a copy of the GNU General
Public License along with this program. If not, see <a
href="http://www.gnu.org/licenses/"
target="_blank">http://www.gnu.org/licenses/</a>.</p>
EOT;
    }
}
