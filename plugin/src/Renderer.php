<?php
namespace MenuIntro;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper as ContentRouteHelper;

class Renderer
{
    public static function renderFromMenuParams(\Joomla\Registry\Registry $p): string
    {
        if (!(int) $p->get('menuintro_enable', 1)) {
            return '';
        }

        $containerClass = (string) $p->get('menuintro_container_class', '');
        $headingTag     = (string) $p->get('menuintro_heading_tag', 'h1');

        // --- NEW: read toggles ---
        $titleEnable   = (int) $p->get('menuintro_title_enable', 0);
        $textEnable    = (int) $p->get('menuintro_text_enable', 0);
        $usePageTitle  = (int) $p->get('menuintro_title_usepage', 0);

        $title = trim((string) $p->get('menuintro_title', ''));

        // If requested, and a custom title is provided, sync it to the page title.
        // When no custom title is provided, do nothing so the normal page title renders as usual.
        if ($usePageTitle && $titleEnable && $title !== '') {
            try {
                $app = Factory::getApplication();
                if ($app->isClient('site')) {
                    $app->getDocument()->setTitle($title);
                }
            } catch (\Throwable $e) {
                // Ignore title sync errors and continue rendering intro
            }
        }

        // 1) Prefer selected Article intro/full article render via core layout
        $articleId = (int) $p->get('menuintro_article', 0);
        if ($articleId) {
            $html = self::renderArticle($articleId);
            if ($html !== '') {
                $titleForWrap = ($title !== '' && $titleEnable) ? $title : '';
                return self::wrap($html, $titleForWrap, $headingTag, $containerClass);
            }
        }

        // 2) Fallback: custom editor ΜΟΝΟ αν είναι ενεργό
        $custom = (string) $p->get('menuintro_text', '');
        if ($textEnable) {
            if (trim($custom) !== '' || trim($title) !== '') {
                $titleForWrap = ($title !== '' && $titleEnable) ? $title : '';
                return self::wrap($custom, $titleForWrap, $headingTag, $containerClass);
            }
        } else {
            // Backward-compat: αν δεν υπάρχει toggle αλλά υπάρχει κείμενο
            if (!$p->exists('menuintro_text_enable') && trim($custom) !== '') {
                $titleForWrap = ($title !== '' && $titleEnable) ? $title : '';
                return self::wrap($custom, $titleForWrap, $headingTag, $containerClass);
            }
        }

        return '';
    }

    private static function renderArticle(int $id): string
    {
        try {
            $app   = Factory::getApplication();
            $content = $app->bootComponent('com_content');
            $factory = $content->getMVCFactory();
            $model  = $factory->createModel('Article', 'Site', ['ignore_request' => true]);

            // Params/state similar to single article view
            $model->setState('params', $app->getParams('com_content'));
            $model->setState('filter.published', [0,1,2]); // allow according to ACL
            $item = $model->getItem($id);

            if (!$item) {
                return '';
            }

            // Slugging/links όπως πριν
            $item->slug    = $item->alias ? ($item->id . ':' . $item->alias) : (string) $item->id;
            $item->catslug = $item->category_alias ? ($item->catid . ':' . $item->category_alias) : (string) $item->catid;
            $item->link    = \Joomla\CMS\Router\Route::_(
                \Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute($item->slug, $item->catslug, $item->language)
            );

            // --- NEW: prepare content like the view does ---
            $item->text = (string) $item->introtext . (string) $item->fulltext;
            $item->event = new \stdClass();

            \Joomla\CMS\Plugin\PluginHelper::importPlugin('content');
            $app->triggerEvent('onContentPrepare', ['com_content.article', &$item, &$item->params, 0]);

            // Render using core layout (inherits template overrides)
            $layout = new \Joomla\CMS\Layout\FileLayout('joomla.content.article');
            $html = (string) $layout->render(['item' => $item, 'params' => $item->params]);

            // Fallback if layout produced nothing: use prepared text
            if (trim($html) === '') {
                $html = $item->text;
            }

            return $html;
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function wrap(string $inner, string $title = '', string $headingTag = 'h1', string $containerClass = 't4-content-intro mb-4'): string
    {
        $out = '<div class="' . htmlspecialchars($containerClass, ENT_QUOTES, 'UTF-8') . '">';
        if ($title !== '') {
            $out .= '<' . $headingTag . ' class="menu-intro__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</' . $headingTag . '>';
        }
        $out .= '<div class="menu-intro__content">' . $inner . '</div></div>';
        return $out;
    }
}
