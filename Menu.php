<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit;

use Piwik\Menu\MenuAdmin;
use Piwik\Piwik;

class Menu extends \Piwik\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu): void
    {
        if (!Piwik::hasUserSuperUserAccess() || Audit::isPremiumActivated()) {
            return;
        }

        $menu->addDiagnosticItem(
            'Audit_MenuTitle',
            $this->urlForAction('index'),
            $orderId = 40
        );
    }
}
