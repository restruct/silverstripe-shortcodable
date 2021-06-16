<?php

namespace Shortcodable\Controllers;

use Shortcodable\Shortcodable;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HeaderField;
use SilverStripe\ORM\DataObject;

class ShortcodableAdminController
    extends Controller
{
    private static $url_segment = 'shortcodable';

    /**
     * @var array
     */
    private static $allowed_actions = [
        'index' => 'CMS_ACCESS_LeftAndMain',
//        'handleEdit' => 'CMS_ACCESS_LeftAndMain',
        'shortcodePlaceHolder' => 'CMS_ACCESS_LeftAndMain'
    ];

    /**
     * @var array
     */
    private static $url_handlers = [
//        'edit/$ShortcodeType!/$Action//$ID/$OtherID' => 'handleEdit'
        'placeholder/$Shortcode!/$ObjectID/$OtherProp' => 'shortcodePlaceHolder'
    ];

    /**
     * @var array
     */
    protected $shortcodedata;

    /**
     * Provides content (form html) for the shortcode dialog
     **/
    public function index()
    {
        $shortcode_info = Shortcodable::shortcode_class_info();
        $shortcode = $this->request->postVar('ShortcodeType');
        $classname = isset($shortcode_info['sc_class_map'][$shortcode]) ? $shortcode_info['sc_class_map'][$shortcode] : '';
        $shortcodeLabel = isset($shortcode_info['sc_label_map'][$shortcode]) ? $shortcode_info['sc_label_map'][$shortcode] : $shortcode;

        // essential fields
        $fields = FieldList::create([
            DropdownField::create('ShortcodeType', '', $shortcode_info['sc_label_map'])
                ->setEmptyString(_t('Shortcodable.SHORTCODETYPE', 'Shortcode type'))
                ->addExtraClass('shortcode-type _form-group')
        ]);

        if ($classname && class_exists($classname)) {
            $classObj = singleton($classname);
            $fields->insertAfter('ShortcodeType', HeaderField::create('ShortCodeHeading', $shortcodeLabel));

            if (is_subclass_of($classObj, DataObject::class)) {
                if (singleton($classname)->hasMethod('getShortcodableRecords')) {
                    $dataObjectSource = singleton($classname)->getShortcodableRecords();
                } elseif (singleton($classname)->hasMethod('get_shortcodable_records')) {
                    $dataObjectSource = singleton($classname)->get_shortcodable_records();
                } else {
                    $dataObjectSource = $classname::get()->map()->toArray();
                }
                $fields->push(
                    DropdownField::create('id', $classObj->singular_name(), $dataObjectSource)
                        ->setHasEmptyDefault(true)
                );
            }

            $attrFields = null; // shortcode may not need any attributes at all
            if ($classObj->hasMethod('getShortcodeFields')) {
                $attrFields = $classObj->getShortcodeFields();
            }
            // Legacy fallback (from when shortcodable was a Trait) probably safe to remove at some point...
            elseif ($classObj->hasMethod('shortcode_attribute_fields')){
                $attrFields = $classObj::shortcode_attribute_fields();
            }
            if ($attrFields) {
                foreach($attrFields as $attrField){
                    $fields->push($attrField);
                }
            }
        }

        // actions
        $actions = FieldList::create();
        if($type = $this->request->postVar('ShortcodeType')) {
            $actions->push(
                FormAction::create('insert', _t('Shortcodable.BUTTONINSERTSHORTCODE', 'Insert shortcode'))
                    //                ->addExtraClass('btn btn-primary font-icon-tick')
                    ->addExtraClass('btn btn-primary font-icon-plus-circled')
                    ->setUseButtonTag(true)
            );
        }

        // form
        $form = Form::create($this, 'ShortcodeForm', $fields, $actions)
            ->loadDataFrom($this->request->postVars())
            ->addExtraClass('htmleditorfield-form htmleditorfield-shortcodable _cms-edit-form');

        $this->extend('updateShortcodeForm', $form);

//        if ($data = $this->getShortcodeData()) {
//            $form->loadDataFrom($data['atts']);
//
//            // special treatment for setting value of UploadFields
//            foreach ($form->Fields()->dataFields() as $field) {
//                if (is_a($field, 'UploadField') && isset($data['atts'][$field->getName()])) {
//                    $field->setValue(['Files' => explode(',', $data['atts'][$field->getName()])]);
//                }
//            }
//        }

        return $form->forTemplate();
    }

    /**
     * Generates shortcode placeholder img url to display inside TinyMCE instead of the shortcode.
     *
     * @return \SilverStripe\Control\HTTPResponse|string|void
     */
    public function shortcodePlaceHolder($request)
    {
        $sc_key = $request->param('Shortcode');
        $object_id = $request->param('ObjectID');
        $sc_class_map = Shortcodable::get_shortcodable_classes_with_placeholders();
        $sc_class = array_key_exists($sc_key, $sc_class_map) ? $sc_class_map[$sc_key] : null;

        if (!$sc_class || !class_exists($sc_class)) {
            return;
        }

        if ($object_id && is_subclass_of($sc_class, DataObject::class)) {
            $object = $sc_class::get()->byID($object_id);
        } else {
            $object = singleton($sc_class);
        }

        if ($object->hasMethod('getShortcodePlaceHolder')) {
            $attributes = null;

//            if ($shortcode = $request->requestVar('sc')) {
//                $shortcode = str_replace("\xEF\xBB\xBF", '', $shortcode); //remove BOM inside string on cursor position...
//                $shortcodeData = singleton('\Silverstripe\Shortcodable\ShortcodableParser')->the_shortcodes([], $shortcode);
//                if (isset($shortcodeData[0])) {
//                    $attributes = $shortcodeData[0]['atts'];
//                }
//            }

            return $object->getShortcodePlaceholder($attributes);
        }
    }
}
