InlineWidgetsBehavior
==========================
Allows to render widgets in page content in Yii2 Framework based projects

- [Extension](soon)

Install
------------

Either run
~~~
$ php composer.phar require howardEagle/Yii2-inline-widgets-behavior "*"
~~~
or add
~~~~
"howardEagle/Yii2-inline-widgets-behavior": "*"
~~~
to the `require` section of your `composer.json file`.

Usage example
-------------

Add a allowed widgets list into `config/main.php`:
~~~
[php]
return array(
    // ...
    'params'=>array(
         // ...
        'runtimeWidgets'=>array(
            'ContactsForm',
            'Comments',
            'common\widgets\LastPosts',
        }
    }
}
[/php]
~~~

Create widgets:
~~~
[php]
class LastPostsWidget extends Widget
{
    public $tpl = 'default';

    public function run()
    {
        $posts = Post::find()->published()->all();
        echo $this->render('LastPosts/' . $this->tpl, array(
            'posts'=>$posts,
        ));
    }
}
[/php]
~~~

Attach the behavior to a main controller:
~~~
[php]
use howard\behaviors\iwb\InlineWidgetBehavior;

class DefaultController extends Controller
{
    public function behaviors()
    {
        return array(
            'InlineWidgetsBehavior'=>array(
                'class'=> InlineWidgetBehavior::className(),
                'namespace'=> 'common\components\widgets', // default namespace (optional)               
                'widgets'=>Yii::app()->params['runtimeWidgets'],
                'startBlock'=> '[*',
                'endBlock'=> '*]',
             ),
        );
    }
}
[/php]
~~~

You can define a global classname suffix like 'Widget':
~~~
[php]
class DefaultController extends Controller
{
    public function behaviors()
    {
        return array(
            'InlineWidgetsBehavior'=>array(
                'class'=> InlineWidgetBehavior::className(),
                'widgets'=>Yii::app()->params['runtimeWidgets'],
                'classSuffix'=> 'Widget',
             ),
        );
    }
}
[/php]
~~~

for using short names 'LastPosts' instead of 'LastPostsWidget' :
~~~
[php]
return array(
    // ...
    'params'=>array(
         // ...
        'runtimeWidgets'=>array(
            'ContactsForm',
            'Comments',
            'common\widgets\LastPosts',
        }
    }
}
[/php]
~~~

For insert widgets in content you can use string of this format in your text:
~~~
<startBlock><WidgetName>[|<attribute>=<value>[;<attribute>=<value>]]<endBlock>
~~~

For rendering widgets in any View you must call `Controller::decodeWidgets()` method for model HTML content. 

For example:
~~~
[php]
<?php $model->text = '
    <h2>Lorem ipsum</h2>
 
    <h2>Latest posts</h2>
    <p>{{w:LastPosts}}</p>
 
    <h2>Latest posts (with parameters)</h2>
    <p>{{w:LastPosts|tpl=small}}</p>
 
    <h2>Latest posts (with inner caching)</h2>
    <p>{{w:LastPosts|tpl=small;cache=300}}</p>
 
    <p>Dolor...</p>
'; ?>
 
<h1><?php echo CHtml::encode($model->title); ?></h1>
<?php echo $this->context->decodeWidgets($model->text); ?>
[/php]
~~~
