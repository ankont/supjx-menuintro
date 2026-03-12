<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.menuintro
 * @copyright   (C) 2025 Kontarinis Andreas — with help from ChatGPT
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;

require_once __DIR__ . '/src/Renderer.php';

class PlgSystemMenuintro extends CMSPlugin
{
    /**
     * Ensure plugin language files are loaded for both site & admin.
     */
    protected function loadPluginLanguage(): void
    {
        // Standard load
        $this->loadLanguage();
        // Fallback explicit paths
        $lang = \Joomla\CMS\Factory::getLanguage();
        $lang->load('plg_system_menuintro', __DIR__);
    }

    /**
     * Add extra fields into com_menus.item form (stored in params)
     */
    public function onContentPrepareForm($form, $data)
    {
        // 1) Τρέξε ΜΟΝΟ για τη φόρμα menu item
        if (!($form instanceof Form) || $form->getName() !== 'com_menus.item') {
            return;
        }

        // 2) Φόρτωσε γλώσσες (για να φαίνονται τα labels)
        $this->loadPluginLanguage();

        // 3) Δήλωσε το path και φόρτωσε τη δική μας φόρμα
        Form::addFormPath(__DIR__ . '/forms');
        $form->loadFile('menuintro', false);
    }

    /**
     * Auto inject mode: insert the intro block before the component without touching template files.
     */
    public function onBeforeRender()
    {
        if ($this->params->get('render_mode', 'template') !== 'auto') return;

        $app = \Joomla\CMS\Factory::getApplication();
        if (!$app->isClient('site')) return;

        $doc = $app->getDocument();
        if ($doc->getType() !== 'html') return;

        $menu = $app->getMenu()->getActive();
        if (!$menu) return;

        $params = $menu->getParams();
        $usePageTitle = (int) $params->get('menuintro_title_usepage', 0);
        $titleEnable  = (int) $params->get('menuintro_title_enable', 0);
        $customTitle  = trim((string) $params->get('menuintro_title', ''));

        $intro = \MenuIntro\Renderer::renderFromMenuParams($params);
        if ($intro === '') return;

        $component = (string) $doc->getBuffer('component');

        if ($usePageTitle && $titleEnable) {
            // Ensure we don't end up with a duplicate heading: strip any intro-rendered heading placeholder
            if ($customTitle !== '') {
                $intro = preg_replace('/<h[1-6][^>]*class=\"[^\"]*menu-intro__title[^\"]*\"[^>]*>.*?<\\/h[1-6]>/is', '', $intro, 1);
            }
            $headingHtml = '';

            if ($customTitle !== '') {
                // Custom title override: render with selected heading tag and remove default heading from component
                $headingTag = (string) $params->get('menuintro_heading_tag', 'h1');
                $headingHtml = '<' . $headingTag . ' class="menu-intro__title">' . htmlspecialchars($customTitle, ENT_QUOTES, 'UTF-8') . '</' . $headingTag . '>';
                $component = self::removeDefaultPageHeading($component);
            } else {
                // Move existing page heading (best-effort extraction)
                [$extracted, $rest] = self::extractDefaultPageHeading($component);
                if ($extracted !== '') {
                    $headingHtml = $extracted;
                    $component = $rest;
                }
            }

            if ($headingHtml !== '') {
                $doc->setBuffer($headingHtml . $intro . $component, 'component');
                return;
            }
        }

        // Fallback/default: intro before component
        if ($component !== null) {
            $doc->setBuffer($intro . $component, 'component');
        }
    }

    /**
     * Extract default page heading wrapper/element from component HTML.
     * @return array{0:string,1:string}
     */
    private static function extractDefaultPageHeading(string $componentHtml): array
    {
        $patterns = [
            '/<div[^>]*class="[^"]*page-header[^"]*"[^>]*>.*?<\/div>/is',
            '/<h[1-6][^>]*class="[^"]*page-title[^"]*"[^>]*>.*?<\/h[1-6]>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $componentHtml, $m)) {
                $extracted = $m[0];
                $remaining = preg_replace($pattern, '', $componentHtml, 1);
                return [$extracted, (string) $remaining];
            }
        }

        return ['', $componentHtml];
    }

    /**
     * Remove default page heading (best-effort) from component HTML.
     */
    private static function removeDefaultPageHeading(string $componentHtml): string
    {
        [, $remaining] = self::extractDefaultPageHeading($componentHtml);
        return $remaining;
    }

    /**
     * Helper to call from template in "template" render mode.
     * Use like this in template where you want it to appear:
     *   <?php
     *     \Joomla\CMS\Plugin\PluginHelper::importPlugin('system', 'menuintro');
     *     if (class_exists('PlgSystemMenuintro')) {
     *       PlgSystemMenuintro::renderActiveMenuIntro();
     *     }
     *   ?>
     */
    public static function renderActiveMenuIntro(): void
    {
        $app = Factory::getApplication();
        if (!$app->isClient('site')) {
            return;
        }
        $menu = $app->getMenu()->getActive();
        if (!$menu) {
            return;
        }
        $html = \MenuIntro\Renderer::renderFromMenuParams($menu->getParams());
        if ($html) {
            echo $html;
        }
    }
}
