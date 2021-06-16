<?php

namespace Shortcodable\Extensions;

use Shortcodable\Shortcodable;
use SilverStripe\Core\Extension;

class HtmlEditorFieldExtension
    extends Extension
{
    public function onBeforeRender()
    {
        $sc_placeholder_code_class_map = Shortcodable::get_shortcodable_classes_with_placeholders();
        $sc_placeholder_codes = array_keys($sc_placeholder_code_class_map);
        $this->owner->setAttribute(
            'data-shortcodableplaceholdercodes',
            json_encode( $sc_placeholder_codes )
        );
    }
}
