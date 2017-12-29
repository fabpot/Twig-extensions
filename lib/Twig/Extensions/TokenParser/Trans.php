<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Twig_Extensions_TokenParser_Trans extends Twig_TokenParser
{
    private $allow_complex = false;

    /**
     * {@inheritdoc}
     */
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $count = null;
        $plural = null;
        $notes = null;

        if (!$stream->test(Twig_Token::BLOCK_END_TYPE)) {
            $body = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
            $body = $this->parser->subparse(array($this, 'decideForFork'));
            $next = $stream->next()->getValue();

            if ('plural' === $next) {
                $count = $this->parser->getExpressionParser()->parseExpression();
                $stream->expect(Twig_Token::BLOCK_END_TYPE);
                $plural = $this->parser->subparse(array($this, 'decideForFork'));

                if ('notes' === $stream->next()->getValue()) {
                    $stream->expect(Twig_Token::BLOCK_END_TYPE);
                    $notes = $this->parser->subparse(array($this, 'decideForEnd'), true);
                }
            } elseif ('notes' === $next) {
                $stream->expect(Twig_Token::BLOCK_END_TYPE);
                $notes = $this->parser->subparse(array($this, 'decideForEnd'), true);
            }
        }

        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        if (!$this->allow_complex) {
            $this->checkTransString($body, $lineno);
        }

        return new Twig_Extensions_Node_Trans($body, $plural, $count, $notes, $lineno, $this->getTag());
    }

    public function decideForFork(Twig_Token $token)
    {
        return $token->test(array('plural', 'notes', 'endtrans'));
    }

    public function decideForEnd(Twig_Token $token)
    {
        return $token->test('endtrans');
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'trans';
    }

    /**
     * Overrides Twig_TokenParser::setParser.
     * Sets the parser associated with this token parser and extract
     * configuration from the parser environment.
     *
     * @param Twig_Parser $parser A Twig_Parser instance
     */
    public function setParser(Twig_Parser $parser)
    {
        parent::setParser($parser);

        // Hack to allow to read the private environment from the $parser.
        // I don't get why it was decided to deprecate the "getEnvironment"
        // from the parser, but... whatever.
        $env = \Closure::bind(function() {return $this->env; }, $parser, get_class($parser))->call($parser);

        if ($env->hasExtension('Twig_Extensions_Extension_I18n')) {
            $this->allow_complex = $env->getExtension('Twig_Extensions_Extension_I18n')->getComplexVars();
        }
    }

    protected function checkTransString(Twig_Node $body, $lineno)
    {
        foreach ($body as $i => $node) {
            if (
                $node instanceof Twig_Node_Text
                ||
                ($node instanceof Twig_Node_Print && $node->getNode('expr') instanceof Twig_Node_Expression_Name)
            ) {
                continue;
            }

            throw new Twig_Error_Syntax(sprintf('The text to be translated with "trans" can only contain references to simple variables'), $lineno);
        }
    }
}

class_alias('Twig_Extensions_TokenParser_Trans', 'Twig\Extensions\TokenParser\TransTokenParser', false);
