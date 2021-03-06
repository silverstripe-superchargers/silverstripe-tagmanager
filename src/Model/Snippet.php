<?php

namespace SilverStripe\TagManager\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\TagManager\Admin\ParamExpander;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Core\ClassInfo;

/**
 * Represents one snippet added to the site with is params configured
 */
class Snippet extends DataObject
{

    use ParamExpander;

    private static $singular_name = "Tag";

    private static $db = [
        "SnippetClass" => "Varchar(255)",
        "SnippetParams" => "Text",
        "Active" => "Enum('on,off,partial', 'on')",
        "Sort" => "Int",
    ];

    private static $has_many = [
        'Pages' => SnippetPage::class,
    ];

    private static $summary_fields = [
        "SnippetSummary" => ["title" => "Tag"],
        "ActiveLabel" => ["title" => "Active"],
    ];

    private static $active_labels = [
        'on' => 'Enabled',
        'off' => 'Disabled',
    ];

    private static $default_sort = "Sort";

    public function getTitle()
    {
        $provider = $this->getSnippetProvider();
        if ($provider) {
            return $provider->getTitle();
        }
        return "(Unconfigured tag)";
    }

    public function getSnippetSummary()
    {
        $provider = $this->getSnippetProvider();
        if ($provider) {
            return $provider->getSummary((array)json_decode($this->SnippetParams, true));
        }
        return "(Unconfigured tag)";
    }

    public function getActiveLabel() {
        return self::$active_labels[$this->Active];
    }

    /**
     * Return the snippet provider attached to this record
     */
    protected function getSnippetProvider()
    {
        if ($this->SnippetClass) {
            return Injector::inst()->get($this->SnippetClass);
        }
    }

    /**
     * Return the snippet provider attached to this record
     */
    protected function getSnippetTypes()
    {
        $types = [];
        foreach (ClassInfo::implementorsOf('SilverStripe\TagManager\SnippetProvider') as $class) {
            $types[$class] = Injector::inst()->get($class)->getTitle();
        }
        return $types;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Main', (new DropdownField(
            'SnippetClass',
            'Tag type',
            $this->getSnippetTypes()
        ))->setEmptyString('(Choose tag type)'));

        $fields->dataFieldByName('Active')->setSource(self::$active_labels);

        $fields->removeByName('Sort');

        $providerFields = null;
        if ($provider = $this->getSnippetProvider()) {
            $providerFields = $provider->getParamFields();
        }
        $this->expandParams('SnippetParams', $providerFields, $fields, 'Root.Main');

        return $fields;
    }

    public function getCMSValidator()
    {
        return new RequiredFields('SnippetClass');
    }

    /**
     * Return the snippets generated by the configured provider
     */
    public function getSnippets()
    {
        if ($provider = $this->getSnippetProvider()) {
            $params = (array)json_decode($this->SnippetParams, true);
            return $provider->getSnippets($params);
        }
    }
}
