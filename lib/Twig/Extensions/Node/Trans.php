<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a trans node.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class Twig_Extensions_Node_Trans extends Twig_Node
{
    public function __construct(Twig_Node $body, Twig_Node $plural = null, Twig_Node_Expression $count = null, Twig_Node $notes = null, Twig_Node $context = null, $lineno, $tag = null)
    {
        parent::__construct(array('count' => $count, 'body' => $body, 'plural' => $plural, 'notes' => $notes, 'context' => $context), array(), $lineno, $tag);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param Twig_Compiler $compiler A Twig_Compiler instance
     */
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $body = $this->getNode('body');

        list($msg, $vars) = $this->compileString($body);

        if (null !== $plural = $this->getNode('plural')) {
            list($msg1, $vars1) = $this->compileString($this->getNode('plural'));

            $vars = array_merge($vars, $vars1);
        }

        if (null !== $context = $this->getNode('context')) {
            $context = trim($context->getAttribute('data'));
        }

        if(null !== $context && null !== $plural){
            $function = 'npgettext';
        }elseif(null !== $context){
            $function = 'pgettext';
        }elseif(null !== $plural){
            $function = 'ngettext';
        }else{
            $function = 'gettext';
        }

        if (null !== $notes = $this->getNode('notes')) {
            $message = trim($notes->getAttribute('data'));

            // line breaks are not allowed cause we want a single line comment
            $message = str_replace(array("\n", "\r"), ' ', $message);
            $compiler->write("// notes: {$message}\n");
        }

        if ($vars) {
            $compiler
                ->write('echo strtr('.$function.'(')
            ;
            if(null !== $context && null !== $plural){
                $compiler
                    ->string($context)
                    ->raw(', ')
                    ->subcompile($msg)
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->getNode('count'))
                    ->raw(')')
                ;
            }elseif(null !== $context){
                $compiler
                    ->string($context)
                    ->raw(', ')
                    ->subcompile($msg)
                ;
            }elseif(null !== $plural){
                $compiler
                    ->subcompile($msg)
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->getNode('count'))
                    ->raw(')')
                ;
            }else{
                $compiler
                    ->subcompile($msg)
                ;
            }

            $compiler->raw('), array(');

            foreach ($vars as $var) {
                if ('count' === $var->getAttribute('name')) {
                    $compiler
                        ->string('%count%')
                        ->raw(' => abs(')
                        ->subcompile($this->getNode('count'))
                        ->raw('), ')
                    ;
                } else {
                    $compiler
                        ->string('%'.$var->getAttribute('name').'%')
                        ->raw(' => ')
                        ->subcompile($var)
                        ->raw(', ')
                    ;
                }
            }

            $compiler->raw("));\n");
        } else {
            $compiler
                ->write('echo '.$function.'(')
            ;
            if(null !== $context && null !== $plural){
                $compiler
                    ->string($context)
                    ->raw(', ')
                    ->subcompile($msg)
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->getNode('count'))
                    ->raw(')')
                ;
            }elseif(null !== $context){
                $compiler
                    ->string($context)
                    ->raw(', ')
                    ->subcompile($msg)
                ;
            }elseif(null !== $plural){
                $compiler
                    ->subcompile($msg)
                    ->raw(', ')
                    ->subcompile($msg1)
                    ->raw(', abs(')
                    ->subcompile($this->getNode('count'))
                    ->raw(')')
                ;
            }else{
                $compiler
                    ->subcompile($msg)
                ;
            }

            $compiler->raw(");\n");
        }
    }

    /**
     * @param Twig_Node $body A Twig_Node instance
     *
     * @return array
     */
    protected function compileString(Twig_Node $body)
    {
        if ($body instanceof Twig_Node_Expression_Name || $body instanceof Twig_Node_Expression_Constant || $body instanceof Twig_Node_Expression_TempName) {
            return array($body, array());
        }

        $vars = array();
        if (count($body)) {
            $msg = '';

            foreach ($body as $node) {
                if (get_class($node) === 'Twig_Node' && $node->getNode(0) instanceof Twig_Node_SetTemp) {
                    $node = $node->getNode(1);
                }

                if ($node instanceof Twig_Node_Print) {
                    $n = $node->getNode('expr');
                    while ($n instanceof Twig_Node_Expression_Filter) {
                        $n = $n->getNode('node');
                    }
                    $msg .= sprintf('%%%s%%', $n->getAttribute('name'));
                    $vars[] = new Twig_Node_Expression_Name($n->getAttribute('name'), $n->getLine());
                } else {
                    $msg .= $node->getAttribute('data');
                }
            }
        } else {
            $msg = $body->getAttribute('data');
        }

        return array(new Twig_Node(array(new Twig_Node_Expression_Constant(trim($msg), $body->getLine()))), $vars);
    }
}
