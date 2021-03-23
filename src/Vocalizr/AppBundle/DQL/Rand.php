<?php

namespace Vocalizr\AppBundle\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;

/**
 * RandFunction ::= "RAND" "(" ")"
 */
class Rand extends FunctionNode
{
    /** @var InputParameter|null */
    private $seed = null;

    /**
     * @param Parser $parser
     *
     * @throws QueryException
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        if (Lexer::T_CLOSE_PARENTHESIS !== $parser->getLexer()->lookahead['type']) {
            $this->seed = $parser->ArithmeticPrimary();
        } else {
            $this->seed = null;
        }
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker)
    {
        $param = '';
        if ($this->seed) {
            $sqlWalker->walkInParameter($this->seed);
            $param = $this->seed->dispatch($sqlWalker);
        }
        $q = 'RAND(' . $param . ')';
        return $q;
    }
}