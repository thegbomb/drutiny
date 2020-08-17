<?php

namespace Drutiny\Report\Backport;

class MustacheStatement extends TextNode {
    const MODE_IF_OR_LOOP = 'if';
    const MODE_NOT = 'not';
    const MODE_VALUE = 'value';
    const MODE_CLOSE = 'close';

    protected $variable;
    protected $isRaw = false;
    protected $operator = '';

    public function __construct(string $variable)
    {
        $this->variable = $variable;
        parent::__construct();
    }

    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    public function getOperatorType()
    {
        switch ($this->operator) {
            case '#':
              return self::MODE_IF_OR_LOOP;
            case '^':
              return self::MODE_NOT;
            case '/':
              return self::MODE_CLOSE;
            default:
              return self::MODE_VALUE;
        }
    }

    public function isNestable()
    {
        return in_array($this->getOperatorType(), [self::MODE_IF_OR_LOOP, self::MODE_NOT]);
    }

    public function isClose()
    {
        return $this->getOperatorType() == self::MODE_CLOSE;
    }

    public static function create(string $operator, string $variable, $is_raw = false)
    {
        $statement = new MustacheStatement($variable);
        $statement->setRaw($is_raw);
        $statement->setOperator($operator);
        return $statement;
    }

    public function setRaw($is_raw = false)
    {
        $this->isRaw = $is_raw;
    }

    public function getVariableName($self_reference = null)
    {
        if ($this->variable != '.') {
            return $this->variable;
        }
        if (!empty($self_reference)) {
          return $self_reference;
        }
        // if ($this->getParent() instanceof MustacheStatement) {
        //     return $this->getParent()->getVariableName();
        // }
        return 'self';
    }

    public function __toString()
    {
        // {{#table_rows}}\r\n  {{#.}} {{.}} |{{/.}}\r\n  {{/table_rows}}
        $syntax = '';
        // {{#foo}}
        if ($this->getOperatorType() == self::MODE_IF_OR_LOOP) {
          $syntax .= $this->renderIfOrLoop($this->getVariableName(), parent::__toString());
        }
        // {{^foo}}
        elseif ($this->getOperatorType() == self::MODE_NOT) {
          $syntax .= '{%~ if '.$this->getVariableName().' is empty or not '.$this->getVariableName().' ~%}';
          $syntax .= parent::__toString();
          $syntax .= '{%~ endif ~%}';
        }
        // {{foo}} or {{{foo}}}
        elseif ($this->getOperatorType() == self::MODE_VALUE) {
          //$syntax .= PHP_EOL;
          $syntax .= '{{'.$this->getVariableName().($this->isRaw ? '|raw' : '').'}}';
          //$syntax .= PHP_EOL;
        }
        // {{/foo}}
        elseif ($this->getOperatorType() == self::MODE_CLOSE) {
          // $syntax .= '{# end '.$this->getVariableName().' #}';
        }
        return $syntax;
    }

    protected function childrenInIfOrLoop()
    {
        $node = reset($this->content);
        $prefix = '';
        $suffix = '';
        if (get_class($node) == 'Drutiny\Report\Backport\TextNode') {
            $text = (string) $node;
            if (strpos($text, "\r") === 0 || strpos($text, "\n") === 0) {
              //echo "Addding prefix...\n";
              //$prefix = "\r\n";
            }
            // else {
            //   var_dump($text);
            // }
        }
        return $prefix.parent::__toString().$suffix;
    }

    protected function renderIfOrLoop($variable, $embedded_code)
    {
        return "{%~ for self in mustache($variable)|filter(s => s is keyed) -%}
        {%- with self %}$embedded_code{% endwith %}{% endfor -%}
        {%- for self in mustache($variable)|filter(s => s is not keyed) %}$embedded_code{% endfor ~%}";
    }
}
