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
                echo '<td><form method="post" action="" style="display:inline">';
                echo '<input type="hidden" name="action" value="delete_media_attachment">';
                echo '<input type="hidden" name="attachment_id" value="' . $id . '">';
                echo '<input type="submit" value="' . _('Delete') . '" style="color:red" '
                    . 'onclick="return confirm(\'' . _('Delete this URL?') . '\')">';
                echo '</form></td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        echo '</fieldset>';

        echo '<fieldset><legend>' . _('Add URL') . '</legend>';
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="action"   value="add_media_attachment">';
        echo '<input type="hidden" name="stock_id" value="' . htmlspecialchars($stockId) . '">';
        echo '<table class="tablestyle_noborder">';
        echo '<tr><td>' . _('URL') . ' <span style="color:red">*</span></td>';
        echo '<td><input type="url" name="url" required maxlength="2048" style="width:100%" '
            . 'placeholder="https://youtube.com/watch?v=... or https://..."></td></tr>';
        echo '<tr><td>' . _('Description') . '</td>';
        echo '<td><input type="text" name="description" maxlength="255" style="width:100%" '
            . 'placeholder="Product demo video, installation guide, etc."></td></tr>';
        echo '</table>';
        echo '<p><input type="submit" value="' . _('Add URL') . '"></p>';
        echo '</form></fieldset>';
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

        $action = $_POST['action'] ?? '';

        if ($action === 'add_media_attachment') {
            $url = trim((string)($_POST['url'] ?? ''));
            if ($url !== '') {
                $description = trim((string)($_POST['description'] ?? ''));
                $this->dao->add($stockId, $url, $description);
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        if ($action === 'delete_media_attachment') {
            $attachId = (int)($_POST['attachment_id'] ?? 0);
            if ($attachId > 0) {
                $this->dao->delete($attachId);
            }
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}
