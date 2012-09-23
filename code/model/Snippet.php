<?php
class Snippet extends DataObject {
    public static $db=array(
                            'Title'=>'Varchar(300)',
                            'Description'=>'Varchar(600)',
                            'Tags'=>'Varchar(400)'
                         );
    
    public static $has_one=array(
                                'Language'=>'SnippetLanguage',
                                'Creator'=>'Member',
                                'LastEditor'=>'Member'
                             );
    
    public static $has_many=array(
                                'Versions'=>'SnippetVersion'
                             );
    
    public static $extensions=array(
                                    'SnippetHierarchy',
                                    "FulltextSearchable('Title,Description,Tags')"
                                );
    
    public static $create_table_options=array(
                                    		'MySQLDatabase'=>'ENGINE=MyISAM'
                                    	);
    
    /**
     * Gets fields used in the cms
     * @return {FieldList} Fields to be used
     */
    public function getCMSFields() {
        return new FieldList(
                            new TabSet('Root',
                                new Tab('Main', _t('CodeBank.MAIN', '_Main'),
                                    new DropdownField('LanguageID', _t('Snippet.LANGUAGE', '_Language'), SnippetLanguage::get()->map('ID', 'Title'), null, null, '---'),
                                    new TextField('Title', _t('Snippet.TITLE', '_Title'), null, 300),
                                    TextareaField::create('Description', _t('Snippet.DESCRIPTION', '_Description'))->setRows(5),
                                    TextareaField::create('Text', _t('Snippet.CODE', '_Code'), $this->getSnippetText())->setRows(30)->addExtraClass('codeBankFullWidth')->addExtraClass('stacked'),
                                    TextareaField::create('Tags', _t('Snippet.TAGS', '_Tags (comma separate)'))->setRows(2)
                                )
                            )
                        );
    }
    
    /**
     * Gets validator used in the cms
     * @return {RequiredFields} Required fields validator
     */
    public function getCMSValidator() {
        return new RequiredFields(
                                'LanguageID',
                                'Title',
                                'Description',
                                'Text'
                            );
    }
    
    /**
     * Sets the creator id for new snippets and sets the last editor id for existing snippets
     */
    protected function onBeforeWrite() {
        parent::onBeforeWrite();
        
        if($this->ID==0) {
            $this->CreatorID=Member::currentUserID();
        }else {
            $this->LastEditorID=Member::currentUserID();
        }
    }
    
    /**
     * Creates the snippet version record after writing
     */
    protected function onAfterWrite() {
        parent::onAfterWrite();
        
        //Write the snippet version record
        $version=new SnippetVersion();
        $version->Text=$this->Text;
        $version->ParentID=$this->ID;
        $version->write();
    }
    
    /**
     * Removes all version history for this snippet before deleting the snippet record
     */
    protected function onBeforeDelete() {
        parent::onBeforeDelete();
        
        DB::query('DELETE FROM SnippetVersion WHERE ParentID='.$this->ID);
    }
    
    /**
     * Gets the text from the latest snippet version
     * @return {string} Snippet text
     */
    public function getSnippetText() {
        $version=$this->Versions()->First();
        if($version) {
            return $version->Text;
        }
    }
    
    /**
     * Gets the id from the latest snippet version
     * @return {string} Snippet text
     */
    public function getCurrentVersionID() {
        $version=$this->Versions()->First();
        if($version) {
            return $version->ID;
        }
    }
    
    /**
     * Gets the summary fields used in gridfield
     * @return {array} Array of field's mapped to labels
     */
    public function summaryFields() {
        return array(
                    'Title'=>_t('Snippet.TITLE', '_Title'),
                    'Description'=>_t('Snippet.DESCRIPTION', '_Description'),
                    'Language.Name'=>_t('Snippet.LANGUAGE', '_Language'),
                    'Tags'=>_t('Snippet.TAGS_COLUMN', '_Tags')
                );
    }
	
	/**
	 * Returns two <span> html DOM elements, an empty <span> with the class 'jstree-pageicon' in front, following by a <span> wrapping around its Title.
	 * @return string a html string ready to be directly used in a template
	 */
	public function getTreeTitle() {
		$treeTitle = sprintf(
			"<span class=\"jstree-pageicon\"></span><span class=\"item\">%s</span>",
			Convert::raw2xml(str_replace(array("\n","\r"),"",$this->Title))
		);
		
		return $treeTitle;
	}
	
	/**
	 * Workaround to get snippets to display in tree, does nothing
	 */
	public function Snippets() {}
	
	/**
	 * Workaround to get snippets to display
	 * @return {bool} Returns boolean true
	 */
    public function hasSnippets() {
        return true;
    }
    
	/**
	 * Return the CSS classes to apply to this node in the CMS tree
	 * @return {string} Classes used in the cms tree
	 */
	public function CMSTreeClasses() {
		$classes=sprintf('class-%s', $this->class);
		
		$classes.=$this->markingClasses();

		return $classes;
	}
}
?>