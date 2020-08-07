<?php

namespace Drutiny\Report\Backport;

class MustacheParser {
    public static function reformat(string $content)
    {
        preg_match_all('/({?{{)\s*([\#\^\/ ])?\s*([a-zA-Z0-9_\.]+|\.)\s?(}}}?)/', $content, $matches, PREG_SET_ORDER);
        $root = new TextNode();
        $node = $root;
        foreach ($matches as $syntax) {
            list($tag, $open, $operator, $variable, $close) = $syntax;
            // Add any content before the next tag as content to the current node.
            $node->appendContent(substr($content, 0, strpos($content, $tag)));

            // Syphin off the remaining content after the end of the tag.
            $content = substr($content, strpos($content, $tag) + strlen($tag));

            // Create a new node for this tag.
            $statement = MustacheStatement::create($operator, $variable, $open == '{{{');
            $node->addChild($statement);

            // If is a nesting statement, set the node to this statement.
            if ($statement->isNestable()) {
                $node = $statement;
            }
            elseif ($statement->isClose()) {
                $node = $node->getParent();
            }
        }
        // Add left over content.
        $root->appendContent($content);
        return (string) $root;
    }
}
