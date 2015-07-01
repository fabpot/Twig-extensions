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
    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $count = null;
        $plural = null;
        $notes = null;
        $body = null;
        $with = null;

        if (!$stream->test(Twig_Token::BLOCK_END_TYPE)) {
            if ($stream->nextIf('with')) {
                $with = $this->parser->getExpressionParser()->parseHashExpression();
            } else {
                $body = $this->parser->getExpressionParser()->parseExpression();
            }
        }

        if (null === $body) {
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
            $body = $this->parser->subparse(array($this, 'decideForFork'));
            $next = $stream->next()->getValue();

            if ('plural' === $next) {
                $countExpr = $this->parser->getExpressionParser()->parseMultitargetExpression();
                $count = new Twig_Node_Expression_Function('abs', $countExpr, $lineno);
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

        $this->checkTransString($body, $lineno);

        $this->checkWithNode($with, $plural, $lineno);

        return new Twig_Extensions_Node_Trans($body, $with, $plural, $count, $notes, $lineno, $this->getTag());
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
     * Gets the tag name associated with this token parser.
     *
     * @param string The tag name
     *
     * @return string
     */
    public function getTag()
    {
        return 'trans';
    }

    private function checkTransString(Twig_NodeInterface $body, $lineno)
    {
        foreach ($body as $i => $node) {
            if (
                $node instanceof Twig_Node_Text
                ||
                ($node instanceof Twig_Node_Print && $node->getNode('expr') instanceof Twig_Node_Expression_Name)
            ) {
                continue;
            }

            throw new Twig_Error_Syntax('The text to be translated with "trans" can only contain references to simple variables', $lineno);
        }
    }

    private function checkWithNode($with, $plural, $lineno)
    {
        if (null !== $with && null !== $plural) {
            $key = new Twig_Node_Expression_Constant('%count%', $lineno);
            if ($with->hasElement($key)) {
                throw new Twig_Error_Syntax('The "count" variable is reserved for "plural"', $lineno);
            }
        }
    }
}
