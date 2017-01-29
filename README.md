InlineWidgetsBehavior
==========================
Allows to render widgets in page content in Yii2 Framework based projects

- [Extension](http://www.yiiframework.com/extension/inline-widget-behavior)

Install
------------

Either run
~~~
$ php composer.phar require --prefer-dist howardEagle/Yii2-inline-widgets-behavior "*"
~~~
or add
~~~~
"howardEagle/Yii2-inline-widgets-behavior": "*"
~~~
to the `require` section of your `composer.json file`.

Usage example
-------------

### Add a allowed widgets list into `config/main.php`:

```php
return [
    // ...
    'params' => [
         // ...
        'runtimeWidgets'=> [
            'common\widgets\LastPosts',
        ]
    ]
]
```

### Create widgets:

```php
class LastPostsWidget extends Widget
{
    public $tpl = 'default';

    public function run()
    {
        $posts = Post::find()->published()->all();
        return $this->render('LastPosts/' . $this->tpl, [
            'posts' => $posts,
        ]);
    }
}
```


### Attach the behavior to a main controller:

```php
use howard\behaviors\iwb\InlineWidgetsBehavior;

class DefaultController extends Controller
{
    public function behaviors()
    {
        return [
            'InlineWidgetsBehavior' => [
                'class'=> InlineWidgetsBehavior::className(),
                'namespace'=> 'common\components\widgets', // default namespace (optional)               
                'widgets'=> \Yii::$app->params['runtimeWidgets'],
                'startBlock'=> '[*',
                'endBlock'=> '*]',
             ],
        ];
    }
}
```


### You can define a global classname suffix like 'Widget':

```php
class DefaultController extends Controller
{
    public function behaviors()
    {
        return [
            'InlineWidgetsBehavior' => [
                'class' => InlineWidgetsBehavior::className(),
                'widgets' => \Yii::$app->params['runtimeWidgets'],
                'classSuffix' => 'Widget',
             ],
        ];
    }
}
```

for using short names 'LastPosts' instead of 'LastPostsWidget' :

```php
return [
    // ...
    'params' => [
         // ...
        'runtimeWidgets' => [
            'ContactsForm',
            'Comments',
            'common\widgets\LastPosts',
        ]
    ]
}
```


For insert widgets in content you can use string of this format in your text:
~~~
<startBlock><WidgetName>[|<attribute>=<value>[;<attribute>=<value>]]<endBlock>
~~~

For rendering widgets in any View you must call `Controller::decodeWidgets()` method for model HTML content. 

### For example:

```php
<?php $model->text = '
    <h2>Lorem ipsum</h2>
 
    <h2>Latest posts</h2>
    <p>[*LastPosts*]</p>
 
    <h2>Latest posts (with parameters)</h2>
    <p>[*LastPosts|tpl=small*]</p>
 
    <h2>Latest posts (with inner caching)</h2>
    <p>[*LastPosts|tpl=small;cache=300*]</p>
 
    <p>Dolor...</p>
'; ?>
 
<h1><?= Html::encode($model->title); ?></h1>
<?= $this->context->decodeWidgets($model->text); ?>
```

to have an access to the model from widgets just specify the 'model' variable in `Controller::decodeWidgets()` method:

```php
<?= $this->context->decodeWidgets($model->text, $model); ?>
```
