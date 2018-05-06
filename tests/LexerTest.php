<?php

namespace Test;

use PHPUnit\Framework\TestCase;
use SearchEngine\Core\Lexer;

class LexerTest extends TestCase
{

    public function testLex()
    {
        $lexer = new Lexer();
        $words = $lexer->lemmatise('Je jouait au ballon avec tommy hier');
        var_dump($words);
    }

}