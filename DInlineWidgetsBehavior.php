<?php
/**
 * DInlineWidgetsBehavior allows render widgets in page content
 *
 * Config:
 * <code>
 * return array(
 *     // ...
 *     'params'=>array(
 *          // ...
 *         'runtimeWidgets'=>array(
 *             'LastPosts',
 *             'Share',
 *             'Comments',
 *         }
 *     }
 * }
 * </code>
 *
 * Widget:
 * <code>
 * class LastPostsWidget extends CWidget
 * {
 *     public $tpl='default';
 *     public $limit=3;
 *
 *     public function run()
 *     {
 *         $posts = Post::model()->published()->last($this->limit)->findAll();
 *         $this->render('LastPosts/' . $this->tpl,array(
 *             'posts'=>$posts,
 *         ));
 *     }
 * }
 * </code>
 *
 * Controller:
 * <code>
 * class Controller extends CController
 * {
 *     public function behaviors()
 *     {
 *         return array(
 *             'InlineWidgetsBehavior'=>array(
 *                 'class'=>'DInlineWidgetsBehavior',
 *                 'location'=>'application.components.widgets',
 *                 'widgets'=>Yii::app()->params['runtimeWidgets'],
 *              ),
 *         );
 *     }
 * }
 * </code>

 *
 * For rendering widgets in View you must call Controller::decodeWidgets() method:
 * <code>
 * $text = '
 *     <h2>Lorem ipsum</h2>
 *     <p>[*LastPosts*]</p>
 *     <p>[*LastPosts|limit=4*]</p>
 *     <p>[*LastPosts|limit=5;tpl=small*]</p>
 *     <p>[*LastPosts|limit=5;tpl=small|cache=300*]</p>
 *     <p>Dolor...</p>
 * ';
 * echo $this->decodeWidgets($text);
 * </code>
 *
 * @author ElisDN <mail@elisdn.ru>
 * @link http://www.elisdn.ru
 */

class DInlineWidgetsBehavior extends CBehavior
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
     * @var string 'widgets.path' if needle use Yii::import()
     */
    public $location = '';
    /**
     * @var array of allowed widgets
     */
    public $widgets = array();

    protected $_widgetToken;

    public function __construct()
    {
        $this->initToken();
    }

    /**
     * Content parser
     * Use $this->decodeWidgets($model->text) in view
     * @param $text
     * @return mixed
     */
    public function decodeWidgets($text)
    {
        $text = $this->replaceBlocks($text);
        $text = $this->clearAutoParagraphs($text);
        $text = $this->processWidgets($text);
        return $text;
    }

    protected function processWidgets($text)
    {
        if (preg_match('|\{' . $this->_widgetToken . ':.+?\}|is', $text)) {
            foreach ($this->widgets as $widget) {
                while (
                    preg_match('|\{' . $this->_widgetToken . ':' . $widget . '(\|([^}]*)?)?\}|is', $text, $p)
                ) {
                    $text = str_replace($p[0], $this->loadWidget($widget, isset($p[2]) ? $p[2] : ''), $text);
                }
            }
            return $text;
        }
        return $text;
    }

    protected function initToken()
    {
        $this->_widgetToken = md5(microtime());
    }

    protected function replaceBlocks($text)
    {
        $text = str_replace($this->startBlock, '{' . $this->_widgetToken . ':', $text);
        $text = str_replace($this->endBlock, '}', $text);
        return $text;
    }

    protected function clearAutoParagraphs($output)
    {
        $output = str_replace('<p>' . $this->startBlock, $this->startBlock, $output);
        $output = str_replace($this->endBlock . '</p>', $this->endBlock, $output);
        return $output;
    }

    protected function loadWidget($name, $attributes='')
    {
        $attrs = $this->parseAttributes($attributes);
        $cache = $this->extractCacheExpireTime($attrs);

        $index = 'widget_' . $name . '_' . serialize($attrs);
        
        if ($cache && $cachedHtml = Yii::app()->cache->get($index)){
             $html = $cachedHtml;
        } else {
            ob_start();
            $widget = $this->createWidget($name, $attrs);
            $widget->run();
            $html = trim(ob_get_clean());
            Yii::app()->cache->set($index, $html, $cache);
        }
        return $html;
    }

    protected function parseAttributes($attributesString)
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

    protected function extractCacheExpireTime(&$attrs)
    {
        $cache = 0;
        if (isset($attrs['cache'])) {
            $cache = (int)$attrs['cache'];
            unset($attrs['cache']);
        }
        return $cache;
    }

    protected function createWidget($name, $attributes)
    {
        $class = $name . 'Widget';

        if (!empty($this->location))
            Yii::import($this->location . '.' . $class);

        $widget = new $class;
        foreach ($attributes as $attribute=>$value){
            $widget->$attribute = trim($value);
        }
        return $widget;
    }
}
