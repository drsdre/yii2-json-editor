<?php

namespace beowulfenator\JsonEditor;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget as BaseWidget;

/**
 * Yii2 wrapper widget for jdorn/json-editor.
 * @author Konstantin Sirotkin <beowulfenator@gmail.com>
 * @link https://github.com/beowulfenator/yii2-json-editor
 * @link https://github.com/jdorn/json-editor
 * @license https://github.com/beowulfenator/yii2-json-editor/blob/master/LICENSE
 */
class JsonEditorWidget extends BaseWidget{

    /**
     * @var array the HTML attributes for the input tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = [];

    /**
     * An array that contains the schema to build the form from.
     * Required. Json::encode will be used.
     * @var array
     */
    public $schema = null;

    /**
     * Id of input that will contain the resulting JSON object.
     * Defaults to null, in which case a hidden input will be rendered.
     * @var string|null
     */
    public $inputId = null;

    /**
     * @var array the HTML attributes for the widget container tag.
     * The "tag" element specifies the tag name of the container element and defaults to "div".
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $containerOptions = [];

    /**
     * Options to be passed to the client. (Schema and starting value are ignored.)
     * List of valid options can be found here:
     * https://github.com/jdorn/json-editor/blob/master/README.md
     * @var array
     */
    public $clientOptions = [];

    /**
     * If true, a hidden input will be rendered to contain the results
     * @var boolean
     */
    private $_renderInput = true;

    public function init(){
        if ($this->name === null && !$this->hasModel() && $this->selector === null) {
            throw new InvalidConfigException("Either 'name', or 'model' and 'attribute' properties must be specified.");
        }

        if (null === $this->schema) {
            throw new InvalidConfigException("You must specify 'schema' property.");
        }

        if ($this->hasModel() && !isset($this->options['id'])) {
            $this->options['id'] = Html::getInputId($this->model, $this->attribute);
        }

        if ($this->hasModel()) {
            $this->name = empty($this->options['name']) ? Html::getInputName($this->model, $this->attribute) : $this->options['name'];
            $this->value = Html::getAttributeValue($this->model, $this->attribute);
        }

        if (!isset($this->containerOptions['id'])) {
            $this->containerOptions['id'] = ($this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->getId()).'-container';
        }

        if ($this->inputId === null) {
            $this->inputId = $this->options['id'];
        } else {
            $this->_renderInput = false;
        }

        parent::init();
        JsonEditorAsset::register($this->getView());
    }

    public function run()
    {
        //prepare data
        $view = $this->getView();

        //render input for results
        if ($this->_renderInput) {
            if ($this->hasModel()) {
                echo Html::activeHiddenInput($this->model, $this->attribute, $this->options);
            } else {
                echo Html::hiddenInput($this->name, $this->value, $this->options);
            }
        }

        //render editor container
        $containerOptions = $this->containerOptions;
        $tag = ArrayHelper::remove($containerOptions, 'tag', 'div');
        echo Html::tag($tag, '', $containerOptions);

        //prepare client options
        $clientOptions = $this->clientOptions;
        $clientOptions['schema'] = $this->schema;
        ArrayHelper::remove($clientOptions, 'startval');
        $clientOptions = Json::encode($clientOptions);

        //prepare element IDs
        $widgetId = $this->id;
        $inputId = $this->inputId;
        $containerId = $this->containerOptions['id'];

        //register js code
        $view->registerJs(
<<<JS
var {$widgetId} = new JSONEditor(document.getElementById('{$containerId}'), {$clientOptions});
try {
    var initialValue = JSON.parse(document.getElementById('{$inputId}').value);
    {$widgetId}.setValue(initialValue);
} catch (e) {
    console.log('Could not parse initial value for {$widgetId}');
}
{$widgetId}.on('change', function() {
    document.getElementById('{$inputId}').value = JSON.stringify({$widgetId}.getValue());
});
JS
, $view::POS_READY);

        parent::run();
    }
}
?>
