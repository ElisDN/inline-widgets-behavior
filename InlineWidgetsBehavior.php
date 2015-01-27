<?php
/**
 * InlineWidgetsBehavior allows render widgets in page content
 *
 * Config:
 * return array(
 *     // ...
 *     'params'=>array(
 *          // ...
 *         'runtimeWidgets'=>array(
 *             'ContactsForm',
 *             'Comments',
 *             'common\widgets\LastPosts',
 *         }
 *     }
 * }
 *
 * Widget:
 * class LastPostsWidget extends Widget
 * {
 *     public $tpl = 'default';
 *    
 *     public function run()
 *     {
 *         $posts = Post::find()->published()->all();
 *         echo $this->render('LastPosts/' . $this->tpl, array(
 *             'posts' => $posts,
 *         ));
 *     }
 * }
 *
 * Controller:
 * use howard\behaviors\iwb\InlineWidgetsBehavior;
 * class DefaultController extends Controller
 * {
 *     public function behaviors()
 *     {
 *         return array(
 *             'InlineWidgetsBehavior'=>array(
 *                 'class' => InlineWidgetsBehavior::className(),
 *                 'namespace' => 'common\components\widgets',
 *                 'widgets' => Yii::app()->params['runtimeWidgets'],
 *              ),
 *         );
 *     }
 * }
 *
 * For rendering widgets in View you must call Controller::decodeWidgets() method:
 * $text = '
 *     <h2>Lorem ipsum</h2>
 *     <p>[*LastPosts*]</p> *
 *     <p>[*LastPosts|tpl=small*]</p>
 *     <p>[*LastPosts|tpl=small|cache=300*]</p>
 *     <p>Dolor...</p>
 * ';
 * echo $this->context->decodeWidgets($text);
 *
 * @authors: ElisDN <mail@elisdn.ru>, HowarD <vovchuck.bogdan@gmail.com>
 * @link http://www.elisdn.ru
 * @version 1.0
 */

namespace howard\behaviors\iwb;
use yii\base\Behavior;

class InlineWidgetsBehavior extends Behavior
{
    /**
     * @var string marker of block begin
     */
    public $startBlock = '[*';
    /**
     * @var string marker of block end
     */
    public $endBlock = '*]';
    /**
     * @var namespace of widgets like 'common\components\widgets'
     */
    public $namespace = '';
    /**
     * @var string global classname suffix like 'Widget'
     */
    public $classSuffix = '';
    /**
     * @var array of allowed widgets
     */
    public $widgets = array();

    protected $_widgetToken;

    public function __construct()
    {
        $this->_initToken();
    }

    /**
     * Content parser
     * Use $this->view->decodeWidgets($model->text) in view
     * @param $text
     * @return mixed
     */
    public function decodeWidgets($text)
    {
        $text = $this->_clearAutoParagraphs($text);
        $text = $this->_replaceBlocks($text);
        $text = $this->_processWidgets($text);
        return $text;
    }

    /**
     * Content cleaner
     * Use $this->view->clearWidgets($model->text) in view
     * @param $text
     * @return mixed
     */
    public function clearWidgets($text)
    {
        $text = $this->_clearAutoParagraphs($text);
        $text = $this->_replaceBlocks($text);
        $text = $this->_clearWidgets($text);
        return $text;
    }
    
    /**
     * Renders widgets
     */		
    protected function _processWidgets($text)
    {
        if (preg_match('|\{' . $this->_widgetToken . ':.+?' . $this->_widgetToken . '\}|is', $text)) {
            foreach ($this->widgets as $alias) {
                $widget = $this->_getClassByAlias($alias);
                while (preg_match('/\{' . $this->_widgetToken . ':' . $widget . '(\|([^}]*)?)?' . $this->_widgetToken . '\}/is', $text, $p)) {
                    $text = str_replace($p[0], $this->_loadWidget($alias, isset($p[2]) ? $p[2] : ''), $text);
                }
            }
            return $text;
        }
        return $text;
    }

    protected function _clearWidgets($text)
    {
        return preg_replace('|\{' . $this->_widgetToken . ':.+?' . $this->_widgetToken . '\}|is', '', $text);
    }

    protected function _initToken()
    {
        $this->_widgetToken = md5(microtime());
    }

    protected function _replaceBlocks($text)
    {
        $text = str_replace($this->startBlock, '{' . $this->_widgetToken . ':', $text);
        $text = str_replace($this->endBlock, $this->_widgetToken . '}', $text);
        return $text;
    }

    protected function _clearAutoParagraphs($output)
    {
        $output = str_replace('<p>' . $this->startBlock, $this->startBlock, $output);
        $output = str_replace($this->endBlock . '</p>', $this->endBlock, $output);
        return $output;
    }

    protected function _loadWidget($name, $attributes = '')
    {
        $attrs = $this->_parseAttributes($attributes);
        $cache = $this->_extractCacheExpireTime($attrs);
        $index = 'widget_' . $name . '_' . serialize($attrs);
        if ($cache && $cachedHtml = \Yii::$app->cache->get($index)) {
            $html = $cachedHtml;
        } else {
            ob_start();
            $widgetClass = $this->_getFullClassName($name);
            $config['class'] = $widgetClass;
            $widget = \Yii::createObject($config);
            $widget->run();
            $html = trim(ob_get_clean());
            \Yii::$app->cache->set($index, $html, $cache);
        }
        return $html;
    }

    protected function _parseAttributes($attributesString)
    {
        $params = explode(';', $attributesString);
        $attrs = array();
        foreach ($params as $param) {
            if ($param) {
                list($attribute, $value) = explode('=', $param);
                if ($value) $attrs[$attribute] = trim($value);
            }
        }
        ksort($attrs);
        return $attrs;
    }

    protected function _extractCacheExpireTime(&$attrs)
    {
        $cache = 0;
        if (isset($attrs['cache'])) {
            $cache = (int)$attrs['cache'];
            unset($attrs['cache']);
        }
        return $cache;
    }

    protected function _getFullClassName($name)
    {
        $widgetClass = $name . $this->classSuffix;
        if ($this->_getClassByAlias($widgetClass) == $widgetClass && $this->namespace)
            $widgetClass = $this->namespace . '\\' . $widgetClass;
        return $widgetClass;
    }

    protected function _getClassByAlias($alias)
    {
        $paths = explode('\\', $alias);
        return array_pop($paths);
    }
} 
