<?php

declare(strict_types=1);

namespace Sevk\Tests;

use PHPUnit\Framework\TestCase;

class MarkupTest extends TestCase
{
    // ============================================
    // BLOCK / TEMPLATE ENGINE TESTS - Variables
    // ============================================

    public function testBlockWithSimpleVariable(): void
    {
        $markup = '<block config=\'{"name":"Alice"}\'>{%name%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Alice', $html);
    }

    public function testBlockWithFallbackValue(): void
    {
        $markup = '<block config=\'{}\'>{%name ?? Guest%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Guest', $html);
    }

    public function testBlockWithFallbackNotUsedWhenValueExists(): void
    {
        $markup = '<block config=\'{"name":"Alice"}\'>{%name ?? Guest%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Alice', $html);
        $this->assertStringNotContainsString('Guest', $html);
    }

    public function testBlockWithMissingVariableRendersEmpty(): void
    {
        $markup = '<block config=\'{}\'>{%missing%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringNotContainsString('{%missing%}', $html);
    }

    public function testBlockPreservesMustacheVariables(): void
    {
        $markup = '<block config=\'{"name":"Alice"}\'>Hello {%name%}, your code is {{code}}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Alice', $html);
        $this->assertStringContainsString('{{code}}', $html);
    }

    // ============================================
    // BLOCK / TEMPLATE ENGINE TESTS - Each Loop
    // ============================================

    public function testBlockWithEachLoop(): void
    {
        $markup = '<block config=\'{"items":["apple","banana","cherry"]}\'>{%#each items as item%}{%item%} {%/each%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('apple', $html);
        $this->assertStringContainsString('banana', $html);
        $this->assertStringContainsString('cherry', $html);
    }

    public function testBlockWithEachLoopObjectItems(): void
    {
        $markup = '<block config=\'{"users":[{"name":"Alice","role":"admin"},{"name":"Bob","role":"user"}]}\'>{%#each users as user%}{%user.name%}:{%user.role%} {%/each%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Alice:admin', $html);
        $this->assertStringContainsString('Bob:user', $html);
    }

    public function testBlockWithEachLoopEmptyArray(): void
    {
        $markup = '<block config=\'{"items":[]}\'>{%#each items as item%}{%item%}{%/each%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringNotContainsString('{%', $html);
    }

    public function testBlockWithEachLoopObjectFallback(): void
    {
        $markup = '<block config=\'{"users":[{"name":"Alice"}]}\'>{%#each users as user%}{%user.name%} - {%user.role ?? unknown%}{%/each%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Alice', $html);
        $this->assertStringContainsString('unknown', $html);
    }

    // ============================================
    // BLOCK / TEMPLATE ENGINE TESTS - If/Else
    // ============================================

    public function testBlockWithIfConditionalTrue(): void
    {
        $markup = '<block config=\'{"showGreeting":true}\'>Before{%#if showGreeting%}Hello!{%/if%}After</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Hello!', $html);
    }

    public function testBlockWithIfConditionalFalse(): void
    {
        $markup = '<block config=\'{"showGreeting":false}\'>Before{%#if showGreeting%}Hello!{%/if%}After</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringNotContainsString('Hello!', $html);
        $this->assertStringContainsString('Before', $html);
        $this->assertStringContainsString('After', $html);
    }

    public function testBlockWithIfElse(): void
    {
        $markup = '<block config=\'{"loggedIn":true}\'>{%#if loggedIn%}Welcome back{%else%}Please sign in{%/if%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Welcome back', $html);
        $this->assertStringNotContainsString('Please sign in', $html);
    }

    public function testBlockWithIfElseFalseBranch(): void
    {
        $markup = '<block config=\'{"loggedIn":false}\'>{%#if loggedIn%}Welcome back{%else%}Please sign in{%/if%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringNotContainsString('Welcome back', $html);
        $this->assertStringContainsString('Please sign in', $html);
    }

    public function testBlockWithIfMissingKey(): void
    {
        $markup = '<block config=\'{}\'>{%#if missing%}Visible{%/if%}Always</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringNotContainsString('Visible', $html);
        $this->assertStringContainsString('Always', $html);
    }

    public function testBlockWithNestedIf(): void
    {
        $markup = '<block config=\'{"a":true,"b":true}\'>{%#if a%}OUTER_VISIBLE{%#if b%}INNER_VISIBLE{%/if%}{%/if%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('OUTER_VISIBLE', $html);
        $this->assertStringContainsString('INNER_VISIBLE', $html);
    }

    public function testBlockWithNestedIfInnerFalse(): void
    {
        $markup = '<block config=\'{"a":true,"b":false}\'>{%#if a%}OUTER_VISIBLE{%#if b%}INNER_HIDDEN{%/if%}{%/if%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('OUTER_VISIBLE', $html);
        $this->assertStringNotContainsString('INNER_HIDDEN', $html);
    }

    // ============================================
    // BLOCK / TEMPLATE ENGINE TESTS - Truthiness
    // ============================================

    public function testBlockIfTruthyWithNonEmptyString(): void
    {
        $markup = '<block config=\'{"val":"hello"}\'>{%#if val%}yes{%else%}no{%/if%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('yes', $html);
    }

    public function testBlockIfTruthyWithEmptyString(): void
    {
        $markup = '<block config=\'{"val":""}\'>{%#if val%}yes{%else%}no{%/if%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('no', $html);
    }

    public function testBlockIfTruthyWithZero(): void
    {
        $markup = '<block config=\'{"val":0}\'>{%#if val%}yes{%else%}no{%/if%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('no', $html);
    }

    public function testBlockIfTruthyWithEmptyArray(): void
    {
        $markup = '<block config=\'{"val":[]}\'>{%#if val%}yes{%else%}no{%/if%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('no', $html);
    }

    public function testBlockIfTruthyWithNonEmptyArray(): void
    {
        $markup = '<block config=\'{"val":[1]}\'>{%#if val%}yes{%else%}no{%/if%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('yes', $html);
    }

    // ============================================
    // BLOCK / TEMPLATE ENGINE TESTS - Elements
    // ============================================

    public function testBlockWithParagraphElement(): void
    {
        $markup = '<block config=\'{"text":"Hello world"}\'>
            <paragraph>{%text%}</paragraph>
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<p', $html);
        $this->assertStringContainsString('Hello world', $html);
    }

    public function testBlockWithHeadingElement(): void
    {
        $markup = '<block config=\'{"title":"Welcome"}\'>
            <heading level="2">{%title%}</heading>
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('Welcome', $html);
        $this->assertStringContainsString('</h2>', $html);
    }

    public function testBlockWithButtonElement(): void
    {
        $markup = '<block config=\'{"url":"https://example.com","label":"Click Me"}\'>
            <button href="{%url%}">{%label%}</button>
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('Click Me', $html);
        $this->assertStringContainsString('<a ', $html);
    }

    public function testBlockWithImageElement(): void
    {
        $markup = '<block config=\'{"src":"https://example.com/img.png"}\'>
            <image src="{%src%}" alt="Logo" width="200" />
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('src="https://example.com/img.png"', $html);
        $this->assertStringContainsString('alt="Logo"', $html);
        $this->assertStringContainsString('width="200"', $html);
    }

    public function testBlockWithSectionElement(): void
    {
        $markup = '<block config=\'{"content":"inside section"}\'>
            <section><paragraph>{%content%}</paragraph></section>
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('inside section', $html);
        $this->assertStringContainsString('<table', $html);
    }

    public function testBlockWithLinkElement(): void
    {
        $markup = '<block config=\'{"url":"https://example.com"}\'>
            <link href="{%url%}">Visit</link>
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<a ', $html);
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('Visit', $html);
    }

    public function testBlockWithRowAndColumns(): void
    {
        $markup = '<block config=\'{"left":"Left Content","right":"Right Content"}\'>
            <row>
                <column><paragraph>{%left%}</paragraph></column>
                <column><paragraph>{%right%}</paragraph></column>
            </row>
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Left Content', $html);
        $this->assertStringContainsString('Right Content', $html);
        $this->assertStringContainsString('sevk-column', $html);
        $this->assertStringContainsString('sevk-row-table', $html);
    }

    // ============================================
    // MULTIPLE BLOCKS IN SAME DOCUMENT
    // ============================================

    public function testMultipleBlocksInSameDocument(): void
    {
        $markup = '
            <block config=\'{"title":"First Block"}\'>
                <heading level="1">{%title%}</heading>
            </block>
            <block config=\'{"text":"Second Block"}\'>
                <paragraph>{%text%}</paragraph>
            </block>
        ';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('First Block', $html);
        $this->assertStringContainsString('Second Block', $html);
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('<p', $html);
    }

    public function testMultipleBlocksWithDifferentConfigs(): void
    {
        $markup = '
            <block config=\'{"show":true}\'>
                {%#if show%}Visible{%/if%}
            </block>
            <block config=\'{"show":false}\'>
                {%#if show%}Hidden{%else%}Fallback{%/if%}
            </block>
        ';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Visible', $html);
        $this->assertStringContainsString('Fallback', $html);
        $this->assertStringNotContainsString('Hidden', $html);
    }

    // ============================================
    // TEMPLATE PATTERNS - Social Links
    // ============================================

    public function testSocialLinksTemplate(): void
    {
        $markup = '<block config=\'{"links":[{"url":"https://twitter.com/test","icon":"https://example.com/twitter.png","label":"Twitter"},{"url":"https://github.com/test","icon":"https://example.com/github.png","label":"GitHub"}]}\'>
            {%#each links as link%}<link href="{%link.url%}"><image src="{%link.icon%}" alt="{%link.label%}" width="24" /></link> {%/each%}
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('https://twitter.com/test', $html);
        $this->assertStringContainsString('https://github.com/test', $html);
        $this->assertStringContainsString('alt="Twitter"', $html);
        $this->assertStringContainsString('alt="GitHub"', $html);
    }

    // ============================================
    // TEMPLATE PATTERNS - Header
    // ============================================

    public function testHeaderTemplate(): void
    {
        $markup = '<block config=\'{"logoUrl":"https://example.com/logo.png","title":"My Company","subtitle":"Newsletter"}\'>
            <section padding="20px">
                <image src="{%logoUrl%}" alt="{%title%}" width="150" />
                <heading level="1">{%title%}</heading>
                {%#if subtitle%}<paragraph>{%subtitle%}</paragraph>{%/if%}
            </section>
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('src="https://example.com/logo.png"', $html);
        $this->assertStringContainsString('My Company', $html);
        $this->assertStringContainsString('Newsletter', $html);
        $this->assertStringContainsString('<h1', $html);
    }

    public function testHeaderTemplateWithoutSubtitle(): void
    {
        $markup = '<block config=\'{"logoUrl":"https://example.com/logo.png","title":"My Company"}\'>
            <section padding="20px">
                <image src="{%logoUrl%}" alt="{%title%}" width="150" />
                <heading level="1">{%title%}</heading>
                {%#if subtitle%}<paragraph>{%subtitle%}</paragraph>{%/if%}
            </section>
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('My Company', $html);
        // The subtitle paragraph should not appear
        $this->assertStringNotContainsString('Newsletter', $html);
    }

    // ============================================
    // TEMPLATE PATTERNS - Unsubscribe Footer
    // ============================================

    public function testUnsubscribeFooterTemplate(): void
    {
        $markup = '<block config=\'{"companyName":"Acme Inc","unsubscribeUrl":"https://example.com/unsubscribe"}\'>
            <section padding="20px" text-align="center">
                <paragraph font-size="12px">{%companyName%}</paragraph>
                <link href="{%unsubscribeUrl%}">Unsubscribe</link>
            </section>
        </block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Acme Inc', $html);
        $this->assertStringContainsString('https://example.com/unsubscribe', $html);
        $this->assertStringContainsString('Unsubscribe', $html);
    }

    // ============================================
    // DOCUMENT STRUCTURE TESTS
    // ============================================

    public function testDocumentStructureDoctype(): void
    {
        $html = \Sevk\Markup\render('<email><body></body></email>');
        $this->assertStringContainsString('<!DOCTYPE html PUBLIC', $html);
        $this->assertStringContainsString('XHTML 1.0 Transitional', $html);
    }

    public function testDocumentStructureHtmlTag(): void
    {
        $html = \Sevk\Markup\render('<email><body></body></email>');
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertStringContainsString('xmlns="http://www.w3.org/1999/xhtml"', $html);
        $this->assertStringContainsString('</html>', $html);
    }

    public function testDocumentStructureHeadTag(): void
    {
        $html = \Sevk\Markup\render('<email><body></body></email>');
        $this->assertStringContainsString('<head>', $html);
        $this->assertStringContainsString('</head>', $html);
        $this->assertStringContainsString('charset=UTF-8', $html);
        $this->assertStringContainsString('viewport', $html);
    }

    public function testDocumentStructureBodyTag(): void
    {
        $html = \Sevk\Markup\render('<email><body></body></email>');
        $this->assertStringContainsString('<body', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertStringContainsString('margin:0', $html);
        $this->assertStringContainsString('padding:0', $html);
    }

    // ============================================
    // ELEMENT RENDERING TESTS
    // ============================================

    public function testParagraphRendering(): void
    {
        $markup = '<paragraph>Hello World</paragraph>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<p', $html);
        $this->assertStringContainsString('Hello World', $html);
        $this->assertStringContainsString('</p>', $html);
    }

    public function testParagraphWithStyles(): void
    {
        $markup = '<paragraph font-size="16px" color="red">Styled text</paragraph>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('font-size:16px', $html);
        $this->assertStringContainsString('color:red', $html);
        $this->assertStringContainsString('Styled text', $html);
    }

    public function testHeadingRendering(): void
    {
        $markup = '<heading level="1">Title</heading>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('Title', $html);
        $this->assertStringContainsString('</h1>', $html);
    }

    public function testHeadingLevel2(): void
    {
        $markup = '<heading level="2">Subtitle</heading>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('Subtitle', $html);
        $this->assertStringContainsString('</h2>', $html);
    }

    public function testHeadingLevel3(): void
    {
        $markup = '<heading level="3">Section</heading>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<h3', $html);
        $this->assertStringContainsString('</h3>', $html);
    }

    public function testHeadingWithStyles(): void
    {
        $markup = '<heading level="1" color="blue" font-size="32px">Big Title</heading>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('color:blue', $html);
        $this->assertStringContainsString('font-size:32px', $html);
    }

    public function testButtonRendering(): void
    {
        $markup = '<button href="https://example.com">Click Me</button>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<a ', $html);
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('Click Me', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    public function testButtonWithStyles(): void
    {
        $markup = '<button href="#" background-color="#007bff" color="white" padding="12px 24px" border-radius="4px">Submit</button>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('background-color:#007bff', $html);
        $this->assertStringContainsString('color:white', $html);
        $this->assertStringContainsString('border-radius:4px', $html);
        $this->assertStringContainsString('Submit', $html);
    }

    public function testButtonMsoCompatibility(): void
    {
        $markup = '<button href="https://example.com" padding="10px 20px">Click</button>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<!--[if mso]>', $html);
        $this->assertStringContainsString('<![endif]-->', $html);
        $this->assertStringContainsString('mso-padding-alt:0px', $html);
    }

    public function testImageRendering(): void
    {
        $markup = '<image src="https://example.com/photo.jpg" alt="Photo" width="600" />';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('src="https://example.com/photo.jpg"', $html);
        $this->assertStringContainsString('alt="Photo"', $html);
        $this->assertStringContainsString('width="600"', $html);
    }

    public function testImageDefaultStyles(): void
    {
        $markup = '<image src="https://example.com/img.png" alt="" />';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('max-width:100%', $html);
        $this->assertStringContainsString('outline:none', $html);
        $this->assertStringContainsString('border:none', $html);
        $this->assertStringContainsString('text-decoration:none', $html);
    }

    public function testSectionRendering(): void
    {
        $markup = '<section><paragraph>Content</paragraph></section>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('role="presentation"', $html);
        $this->assertStringContainsString('Content', $html);
    }

    public function testSectionWithStyles(): void
    {
        $markup = '<section background-color="#f0f0f0" padding="20px"><paragraph>Styled section</paragraph></section>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('background-color:#f0f0f0', $html);
        $this->assertStringContainsString('padding:20px', $html);
    }

    public function testContainerRendering(): void
    {
        $markup = '<container max-width="600px"><paragraph>Contained</paragraph></container>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('role="presentation"', $html);
        $this->assertStringContainsString('Contained', $html);
    }

    public function testContainerWithBorderRadius(): void
    {
        $markup = '<container border-radius="8px" background-color="#fff"><paragraph>Rounded</paragraph></container>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('border-radius:8px', $html);
        $this->assertStringContainsString('border-collapse:separate', $html);
    }

    public function testRowAndColumnRendering(): void
    {
        $markup = '<row>
            <column><paragraph>Col 1</paragraph></column>
            <column><paragraph>Col 2</paragraph></column>
        </row>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Col 1', $html);
        $this->assertStringContainsString('Col 2', $html);
        $this->assertStringContainsString('sevk-column', $html);
        $this->assertStringContainsString('sevk-row-table', $html);
    }

    public function testRowWithGap(): void
    {
        $markup = '<row gap="16">
            <column><paragraph>Left</paragraph></column>
            <column><paragraph>Right</paragraph></column>
        </row>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('sevk-gap', $html);
        $this->assertStringContainsString('width:16px', $html);
    }

    public function testColumnDefaultVerticalAlign(): void
    {
        $markup = '<row><column><paragraph>Cell</paragraph></column></row>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('vertical-align:top', $html);
    }

    public function testDividerRendering(): void
    {
        $markup = '<divider />';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<hr', $html);
    }

    public function testDividerWithStyles(): void
    {
        $markup = '<divider border-color="#ccc" border-width="2px" />';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<hr', $html);
        $this->assertStringContainsString('border-color:#ccc', $html);
        $this->assertStringContainsString('border-width:2px', $html);
    }

    public function testListUnorderedRendering(): void
    {
        $markup = '<list><li>Item 1</li><li>Item 2</li></list>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<ul', $html);
        $this->assertStringContainsString('<li', $html);
        $this->assertStringContainsString('Item 1', $html);
        $this->assertStringContainsString('Item 2', $html);
        $this->assertStringContainsString('</ul>', $html);
    }

    public function testListOrderedRendering(): void
    {
        $markup = '<list type="ordered"><li>First</li><li>Second</li></list>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<ol', $html);
        $this->assertStringContainsString('First', $html);
        $this->assertStringContainsString('Second', $html);
        $this->assertStringContainsString('</ol>', $html);
    }

    public function testCodeBlockRendering(): void
    {
        $markup = '<codeblock>const x = 42;</codeblock>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('<code>', $html);
        $this->assertStringContainsString('const x = 42;', $html);
    }

    public function testCodeBlockWithLanguage(): void
    {
        $markup = '<codeblock language="javascript">const x = 42;</codeblock>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('<code>', $html);
        // Should have One Dark theme background
        $this->assertStringContainsString('#282c34', $html);
    }

    public function testCodeBlockOneDarkThemeStyles(): void
    {
        $markup = '<codeblock language="javascript">let a = 1;</codeblock>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('font-family', $html);
        $this->assertStringContainsString('background-color:#282c34', $html);
    }

    // ============================================
    // SELF-CLOSING BLOCK TAG
    // ============================================

    public function testSelfClosingBlockTag(): void
    {
        $markup = '<block template="Hello World" config=\'{}\' />';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Hello World', $html);
    }

    public function testSelfClosingBlockTagWithVariable(): void
    {
        $markup = '<block template="Hello {%name%}" config=\'{"name":"Alice"}\' />';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Hello Alice', $html);
    }

    // ============================================
    // BLOCK WITH EMPTY/MISSING CONFIG
    // ============================================

    public function testBlockWithEmptyConfig(): void
    {
        $markup = '<block config=\'{}\'><paragraph>Static content</paragraph></block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('Static content', $html);
    }

    public function testBlockWithNoContent(): void
    {
        $markup = '<block config=\'{}\'></block>';
        $html = \Sevk\Markup\render($markup);
        // Should render document structure even if block is empty
        $this->assertStringContainsString('<!DOCTYPE html', $html);
    }

    // ============================================
    // LINK RENDERING
    // ============================================

    public function testLinkRendering(): void
    {
        $markup = '<link href="https://example.com">Click here</link>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('<a ', $html);
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('Click here', $html);
    }

    public function testLinkWithStyles(): void
    {
        $markup = '<link href="https://example.com" color="blue" text-decoration="underline">Styled link</link>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('color:blue', $html);
        $this->assertStringContainsString('text-decoration:underline', $html);
    }

    // ============================================
    // COMBINED IF + EACH TESTS
    // ============================================

    public function testCombinedIfAndEachInSameBlock(): void
    {
        $markup = '<block config=\'{"title":"My List","items":["alpha","beta","gamma"]}\'>{%#if title%}<heading level="2">{%title%}</heading>{%/if%}{%#each items as item%}<paragraph>{%item%}</paragraph>{%/each%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringContainsString('My List', $html);
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('alpha', $html);
        $this->assertStringContainsString('beta', $html);
        $this->assertStringContainsString('gamma', $html);
    }

    public function testIfFalseSkipsWrappedEach(): void
    {
        $markup = '<block config=\'{"show":false,"items":["alpha","beta","gamma"]}\'>{%#if show%}{%#each items as item%}<paragraph>{%item%}</paragraph>{%/each%}{%/if%}</block>';
        $html = \Sevk\Markup\render($markup);
        $this->assertStringNotContainsString('alpha', $html);
        $this->assertStringNotContainsString('beta', $html);
        $this->assertStringNotContainsString('gamma', $html);
    }

    // ============================================
    // LANG AND DIR SUPPORT
    // ============================================

    public function testLangAttributeFromRootTag(): void
    {
        $html = \Sevk\Markup\render('<mail lang="fr"><body></body></mail>');
        $this->assertStringContainsString('lang="fr"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
    }

    public function testDirAttributeFromRootTag(): void
    {
        $html = \Sevk\Markup\render('<mail dir="rtl"><body></body></mail>');
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('lang="en"', $html);
    }

    public function testLangAndDirAttributesFromRootTag(): void
    {
        $html = \Sevk\Markup\render('<mail lang="ar" dir="rtl"><body></body></mail>');
        $this->assertStringContainsString('lang="ar"', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
    }

    public function testLangAndDirDefaultValues(): void
    {
        $html = \Sevk\Markup\render('<mail><body></body></mail>');
        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
    }

    public function testLangAndDirWithEmailTag(): void
    {
        $html = \Sevk\Markup\render('<email lang="de" dir="ltr"><body></body></email>');
        $this->assertStringContainsString('lang="de"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
    }
}
