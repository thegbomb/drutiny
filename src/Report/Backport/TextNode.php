<?php

namespace Drutiny\Report\Backport;

class TextNode {
    protected array $content = [];
    protected $text;
    protected TextNode $parent;
    public function __construct(string $content = '')
    {
        $this->text = $content;
    }
    public function appendContent(string $content)
    {
        $this->addChild(new TextNode($content));
        return $this;
    }
    public function addChild(TextNode $node)
    {
        $this->content[] = $node;
        $node->setParent($this);
        return $this;
    }
    public function setParent(TextNode $node)
    {
        $this->parent = $node;
        return $this;
    }
    public function getParent()
    {
        return $this->parent;
    }
    public function __toString()
    {
        return $this->text.implode('', $this->content);
    }
    // public function getTree()
    // {
    //
    // }
}
