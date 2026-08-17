<?php

namespace Ksfraser\FA_ProductAttributes\Tabs;

use FrontAccounting\ProductAttributes\Plugin\AbstractTab;
use Ksfraser\FA_ProductAttributes\Dao\MediaAttachmentsDao;

class UrlsTab extends AbstractTab
{
    /** @var MediaAttachmentsDao */
    private $dao;

    public function __construct(MediaAttachmentsDao $dao)
    {
        $this->dao = $dao;
    }

    public function getName(): string
    {
        return 'product_urls';
    }

    public function getTabKey(): string
    {
        return 'product_urls';
    }

    public function getTabLabel(): string
    {
        return _('URLs');
    }

    public function renderTabContent(string $stockId): void
    {
        $this->handlePostActions($stockId);

        $items = ($stockId !== '') ? $this->dao->listByStockId($stockId) : [];

        echo '<fieldset><legend>' . _('URLs') . '</legend>';

        if (empty($items)) {
            echo '<p>' . _('No URLs added yet.') . '</p>';
        } else {
            echo '<table class="tablestyle2">';
            echo '<tr><th>' . _('URL') . '</th><th>' . _('Description') . '</th>'
                . '<th>' . _('Date') . '</th><th></th></tr>';
            foreach ($items as $item) {
                $id   = (int)($item['id'] ?? 0);
                $url  = htmlspecialchars((string)($item['url'] ?? ''));
                $desc = htmlspecialchars((string)($item['description'] ?? ''));
                $date = htmlspecialchars((string)($item['created_date'] ?? ''));
                echo '<tr>';
                echo '<td><a href="' . $url . '" target="_blank">' . $url . '</a></td>';
                echo '<td>' . $desc . '</td>';
                echo '<td>' . $date . '</td>';
                echo '<td><button type="submit" name="pa_url_delete" value="' . $id . '" '
                    . 'style="color:red" formnovalidate '
                    . 'onclick="return confirm(\'' . _('Delete this URL?') . '\')">'
                    . _('Delete') . '</button></td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        echo '</fieldset>';

        echo '<fieldset><legend>' . _('Add URL') . '</legend>';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';
        echo '<table class="tablestyle_noborder">';
        echo '<tr><td>' . _('URL') . ' <span style="color:red">*</span></td>';
        echo '<td><input type="url" name="url" maxlength="2048" style="width:100%" '
            . 'placeholder="https://youtube.com/watch?v=... or https://..."></td></tr>';
        echo '<tr><td>' . _('Description') . '</td>';
        echo '<td><input type="text" name="description" maxlength="255" style="width:100%" '
            . 'placeholder="Product demo video, installation guide, etc."></td></tr>';
        echo '</table>';
        echo '<p><input type="submit" name="pa_url_add" value="' . _('Add URL') . '"></p>';
        echo '</fieldset>';
    }

    public function handleDelete(string $stockId): void
    {
        $items = $this->dao->listByStockId($stockId);
        foreach ($items as $item) {
            $this->dao->delete((int)($item['id'] ?? 0));
        }
    }

    private function handlePostActions(string $stockId): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $stockId === '') {
            return;
        }

        if (isset($_POST['pa_url_add'])) {
            $url = trim((string)($_POST['url'] ?? ''));
            if ($url === '') {
                if (function_exists('display_error')) {
                    display_error(_('A URL is required.'));
                }
                return;
            }
            $description = trim((string)($_POST['description'] ?? ''));
            $this->dao->add($stockId, $url, $description);
            if (function_exists('display_notification')) {
                display_notification(_('URL added.'));
            }
            return;
        }

        if (isset($_POST['pa_url_delete'])) {
            $attachId = (int)$_POST['pa_url_delete'];
            if ($attachId > 0) {
                $this->dao->delete($attachId);
                if (function_exists('display_notification')) {
                    display_notification(_('URL deleted.'));
                }
            }
            return;
        }
    }
}
