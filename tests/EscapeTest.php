<?php
/**
 * Copyright 2016 Alexander Fedyashov
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

namespace Layershifter\Gumbo\Tests;

use Layershifter\Gumbo\Parser;

class EscapeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test parsing of HTML with <img> element.
     *
     * @void
     */
    public function testNewLine()
    {
        $html = '<p>hello\nthere</p>';
        $document = Parser::load($html);

        static::assertEquals(1, $document->childNodes->length);
        static::assertEquals($html . chr(10), $document->saveHTML());

        $node = $document->childNodes->item(0);

        static::assertInstanceOf('DOMElement', $node);
        static::assertEquals('p', $node->nodeName);
        static::assertEquals('hello\nthere', $node->textContent);
        static::assertEquals(1, $node->childNodes->length);
        static::assertEquals(0, $node->attributes->length);
    }

    // TODO: Ошибка парсинга ошибочной конструкции.

//    /**
//     * Test UNQUOTED_ATTR_VALUE in <h2><span start=</h2>.
//     *
//     * @void
//     */
//    public function testUnQuotedAttrValue()
//    {
//        $html = '<h2><span start=</h2><p></p>';
//        $document = Parser::load($html);
//
//        // Base tests.
//
//   //     static::assertEquals(2, $document->childNodes->length);
//        static::assertEquals($html . chr(10), $document->saveHTML());
//
//        // Tests for child nodes.
//
//    }

    /**
     * Test parsing of HTML with <script> element.
     *
     * @void
     */
    public function testScript()
    {
        $html = <<<'HTML'
<script>
$(document).ready(function(){
    $("#btn1").click(function(){
        var test = 'test' + "\n" + 
         'test' + 1;
        $("#test1").text('Hello world!' + );
    });
    $("#btn2").click(function(){
        $("#test2").html("<b>Hello world!</b>");
    });
});
</script>
HTML;
        $document = Parser::load($html);

        static::assertEquals(1, $document->childNodes->length);
        static::assertEquals($html . chr(10), $document->saveHTML());

        $node = $document->childNodes->item(0);

        static::assertInstanceOf('DOMElement', $node);
        static::assertEquals('script', $node->nodeName);
        static::assertEquals(1, $node->childNodes->length);
        static::assertEquals(0, $node->attributes->length);
    }

    /**
     * Test parsing of text within <body> element.
     *
     * @dataProvider textProvider()
     *
     * @param string $html
     * @param string $text
     * @param bool   $performEqualHtml
     *
     * @return void
     */
    public function testText($html, $text, $performEqualHtml = true)
    {
        $document = Parser::load($html);

        static::assertEquals(1, $document->childNodes->length);

        if ($performEqualHtml) {
            static::assertEquals($html . chr(10), $document->saveHTML());
        }

        $htmlNode = $document->childNodes->item(0);

        static::assertInstanceOf('DOMElement', $htmlNode);
        static::assertEquals('html', $htmlNode->nodeName);
        static::assertEquals(1, $htmlNode->childNodes->length);
        static::assertEquals(0, $htmlNode->attributes->length);

        $bodyNode = $htmlNode->childNodes->item(0);

        static::assertInstanceOf('DOMElement', $bodyNode);
        static::assertEquals('body', $bodyNode->nodeName);
        static::assertEquals($text, $bodyNode->textContent);
        static::assertEquals(1, $bodyNode->childNodes->length);
        static::assertEquals(0, $bodyNode->attributes->length);

        $textNode = $bodyNode->childNodes->item(0);

        static::assertInstanceOf('DOMText', $textNode);
        static::assertEquals($text, $textNode->textContent);
    }

    /**
     * Provides test snippets for testText().
     *
     * @return array
     */
    public function textProvider()
    {
        return [
            [
                '<html><body>a</body></html>',
                'a'
            ],
            [
                '<html><body>< aaa ></body></html>',
                '< aaa >',
                false
            ],
            [
                '<html><body>a <& <* <4</body></html>',
                'a <& <* <4',
                false
            ],
            [
                '<html><body>a > test</body></html>',
                'a > test',
                false
            ],
            [
                '<html><body>a < test</body></html>',
                'a < test',
                false
            ],
            [
                '<html><body>a < тест</body></html>',
                'a < тест',
                false
            ],
            [
                '<html><body>a &amp; b</body></html>',
                'a &amp; b',
                false
            ]
        ];
    }

    /**
     * Test parsing of text within <body> element.
     *
     * @return void
     */
    public function testTextFromStart()
    {
        $html = <<<HTML
<html>
<body>
a &amp;
<div>
    1
    <div>b</div>
    2
</div>
c
</body>
</html>
HTML;

        $document = Parser::load($html);

        static::assertEquals(1, $document->childNodes->length);

        $htmlNode = $document->childNodes->item(0);

        static::assertInstanceOf('DOMElement', $htmlNode);
        static::assertEquals('html', $htmlNode->nodeName);
        static::assertEquals(1, $htmlNode->childNodes->length);
        static::assertEquals(0, $htmlNode->attributes->length);

        $bodyNode = $htmlNode->childNodes->item(0);

        static::assertInstanceOf('DOMElement', $bodyNode);
        static::assertEquals('body', $bodyNode->nodeName);
        static::assertEquals(3, $bodyNode->childNodes->length);
        static::assertEquals(0, $bodyNode->attributes->length);

        $textNode1 = $bodyNode->childNodes->item(0);

        static::assertInstanceOf('DOMText', $textNode1);
        static::assertEquals(PHP_EOL . 'a &amp;' . PHP_EOL, $textNode1->textContent);

        $divNode = $bodyNode->childNodes->item(1);

        static::assertInstanceOf('DOMElement', $divNode);
        static::assertEquals(3, $divNode->childNodes->length);
        static::assertEquals(0, $divNode->attributes->length);

        $innerNode = $divNode->childNodes->item(1);

        static::assertInstanceOf('DOMElement', $innerNode);
        static::assertEquals(1, $innerNode->childNodes->length);
        static::assertEquals(0, $innerNode->attributes->length);
        static::assertEquals('b', $innerNode->textContent);

        $textNode2 = $bodyNode->childNodes->item(2);

        static::assertInstanceOf('DOMText', $textNode2);
        static::assertEquals(PHP_EOL . 'c' . PHP_EOL, $textNode2->textContent);
    }

    /**
     * Test parsing of HTML with <br> element.
     *
     * @void
     */
    public function testVoidBr()
    {
        $html = '<br>';
        $document = Parser::load($html);

        static::assertEquals(1, $document->childNodes->length);
        static::assertEquals($html . chr(10), $document->saveHTML());

        $node = $document->childNodes->item(0);

        static::assertInstanceOf('DOMElement', $node);
        static::assertEquals('br', $node->nodeName);
        static::assertEquals('', $node->textContent);
        static::assertEquals(0, $node->childNodes->length);
        static::assertEquals(0, $node->attributes->length);
    }

    /**
     * Test parsing of HTML with <img> element.
     *
     * @void
     */
    public function testVoidImg()
    {
        $html = '<img src="http://www.mozilla.org/favicon.ico">';
        $document = Parser::load($html);

        static::assertEquals(1, $document->childNodes->length);
        static::assertEquals($html . chr(10), $document->saveHTML());

        $node = $document->childNodes->item(0);

        static::assertInstanceOf('DOMElement', $node);
        static::assertEquals('img', $node->nodeName);
        static::assertEquals('', $node->textContent);
        static::assertEquals(0, $node->childNodes->length);
        static::assertEquals(1, $node->attributes->length);
        static::assertEquals('http://www.mozilla.org/favicon.ico', $node->attributes->item(0)->nodeValue);
    }

    /**
     * Test parsing of valid HTML w/ whitespace.
     *
     * @param string $inputHtml
     *
     * @dataProvider whitespaceProvider
     *
     * @void
     */
    public function testWhitespace($inputHtml)
    {
        $canonicalHTML = '<p class="foo">hello there</p><p>u</p>';
        $document = Parser::load($inputHtml);

        static::assertEquals(2, $document->childNodes->length);
        static::assertEquals($canonicalHTML . chr(10), $document->saveHTML());

        $node = $document->childNodes->item(0);

        static::assertInstanceOf('DOMElement', $node);
        static::assertEquals('p', $node->nodeName);
        static::assertEquals('hello there', $node->textContent);
        static::assertEquals(1, $node->childNodes->length);
        static::assertEquals(1, $node->attributes->length);
        static::assertEquals('class', $node->attributes->item(0)->nodeName);
        static::assertEquals('foo', $node->attributes->item(0)->nodeValue);
    }

    /**
     * Provides test snippets for testWhitespace().
     *
     * @return array
     */
    public function whitespaceProvider()
    {
        return [
            ['<p class = "foo">hello there</p><p>u</p>'],
            ['<p class="foo"  >hello there</p><p>u</p>'],
            //['<p \nclass="foo">hello there</p><p>u</p>'],
            ['<p class="foo">hello there</p ><p>u</p>']
        ];
    }
}
