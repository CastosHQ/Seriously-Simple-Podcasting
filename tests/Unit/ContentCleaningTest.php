<?php

namespace Tests\Unit;

use Tests\Support\UnitTester;

class ContentCleaningTest extends \Codeception\Test\Unit
{

    protected UnitTester $tester;

    protected function _before()
    {
    }

    // tests
    public function testFeedContentCleaning()
    {
        // Test the core logic of feed content cleaning without WordPress dependencies
        
        $test_cases = [
            // Remove iframes
            'Content with <iframe src="video.mp4"></iframe> iframe' => 'Content with  iframe',
            'Multiple <iframe>content</iframe> and <iframe src="test">more</iframe>' => 'Multiple  and',
            
            // Remove style tags with content
            'Text <style>body { color: red; }</style> more text' => 'Text  more text',
            'Multiple <style>.class{}</style> styles <style>div{}</style>' => 'Multiple  styles',
            
            // Remove script tags with content
            'Content <script>alert("test");</script> after' => 'Content  after',
            'Multiple <script>var x=1;</script> scripts <script>console.log();</script>' => 'Multiple  scripts',
            
            // Convert <br> to newlines
            'Line 1<br>Line 2<br>Line 3' => "Line 1\nLine 2\nLine 3",
            'Text<br/>More<br />End' => "Text\nMore\nEnd",
            
            // Strip tags except allowed ones
            'Keep <p>paragraph</p> and <a href="#">link</a>' => 'Keep <p>paragraph</p> and <a href="#">link</a>',
            'Remove <div>div</div> but keep <strong>strong</strong>' => 'Remove div but keep <strong>strong</strong>',
            'Remove <span>span</span> and <img src="test.jpg">' => 'Remove span and',
            
            // Remove empty paragraphs
            '<p></p>Content<p></p>More<p></p>' => 'ContentMore',
            'Start<p></p><p>Keep this</p><p></p>End' => 'Start<p>Keep this</p>End',
        ];
        
        foreach ($test_cases as $input => $expected) {
            $result = $this->simulateFeedContentCleaning($input);
            $this->assertEquals($expected, $result, "Content cleaning failed for: '{$input}'");
        }
    }

    public function testIframeRemoval()
    {
        // Test iframe removal patterns
        
        $iframe_cases = [
            '<iframe src="video.mp4"></iframe>' => '',
            '<iframe width="560" height="315" src="https://www.youtube.com/embed/test"></iframe>' => '',
            'Before <iframe>content inside</iframe> after' => 'Before  after',
            '<iframe src="test.mp4" allowfullscreen></iframe>' => '',
            // Self-closing iframes
            '<iframe src="test.mp4" />' => '',
        ];
        
        foreach ($iframe_cases as $input => $expected) {
            $result = preg_replace('/<iframe[^>]*(?:\/>|>.*?<\/iframe>)/s', '', $input);
            $this->assertEquals($expected, $result, "Iframe removal failed for: '{$input}'");
        }
    }

    public function testStyleTagRemoval()
    {
        // Test style tag removal with content
        
        $style_cases = [
            '<style>body { color: red; }</style>' => '',
            '<style type="text/css">.class { margin: 0; }</style>' => '',
            'Text <style>.test{}</style> more' => 'Text  more',
            '<style>
                .multiline {
                    color: blue;
                }
            </style>' => '',
        ];
        
        foreach ($style_cases as $input => $expected) {
            $result = preg_replace('/<style[^>]*>.*?<\/style>/s', '', $input);
            $this->assertEquals($expected, $result, "Style removal failed for: '{$input}'");
        }
    }

    public function testScriptTagRemoval()
    {
        // Test script tag removal with content
        
        $script_cases = [
            '<script>alert("test");</script>' => '',
            '<script type="text/javascript">var x = 1;</script>' => '',
            'Text <script>console.log("test");</script> more' => 'Text  more',
            '<script>
                function test() {
                    return true;
                }
            </script>' => '',
        ];
        
        foreach ($script_cases as $input => $expected) {
            $result = preg_replace('/<script[^>]*>.*?<\/script>/s', '', $input);
            $this->assertEquals($expected, $result, "Script removal failed for: '{$input}'");
        }
    }

    public function testBrTagConversion()
    {
        // Test <br> tag conversion to newlines
        
        $br_cases = [
            'Line 1<br>Line 2' => "Line 1\nLine 2",
            'Line 1<br/>Line 2' => "Line 1\nLine 2",
            'Line 1<br />Line 2' => "Line 1\nLine 2",
            'Multiple<br>breaks<br>here' => "Multiple\nbreaks\nhere",
        ];
        
        foreach ($br_cases as $input => $expected) {
            $result = preg_replace('/<br\s*\/?>/i', PHP_EOL, $input);
            $this->assertEquals($expected, $result, "BR conversion failed for: '{$input}'");
        }
    }

    public function testAllowedTagsStripping()
    {
        // Test strip_tags with allowed tags
        
        $allowed_tags = '<p>,<a>,<ul>,<ol>,<li>,<strong>,<em>,<h2>,<h3>,<h4>,<h5>,<label>';
        
        $tag_cases = [
            // Keep allowed tags
            '<p>Paragraph</p>' => '<p>Paragraph</p>',
            '<a href="test.com">Link</a>' => '<a href="test.com">Link</a>',
            '<strong>Bold</strong>' => '<strong>Bold</strong>',
            '<ul><li>Item</li></ul>' => '<ul><li>Item</li></ul>',
            
            // Remove disallowed tags
            '<div>Content</div>' => 'Content',
            '<span>Text</span>' => 'Text',
            '<img src="test.jpg" alt="test">' => '',
            '<script>alert();</script>' => 'alert();',
        ];
        
        foreach ($tag_cases as $input => $expected) {
            $result = strip_tags($input, $allowed_tags);
            $this->assertEquals($expected, $result, "Tag stripping failed for: '{$input}'");
        }
    }

    public function testEmptyParagraphRemoval()
    {
        // Test empty paragraph removal
        
        $empty_p_cases = [
            '<p></p>' => '',
            '<p></p>Content<p></p>' => 'Content',
            'Start<p></p>Middle<p></p>End' => 'StartMiddleEnd',
            '<p>Keep</p><p></p><p>This</p>' => '<p>Keep</p><p>This</p>',
        ];
        
        foreach ($empty_p_cases as $input => $expected) {
            $result = str_replace('<p></p>', '', $input);
            $this->assertEquals($expected, $result, "Empty paragraph removal failed for: '{$input}'");
        }
    }

    public function testComplexContentCleaning()
    {
        // Test complex content with multiple cleaning operations
        
        $complex_content = '
            <div class="content">
                <p>This is a paragraph with <strong>bold text</strong>.</p>
                <style>.hidden { display: none; }</style>
                <p>Another paragraph with <a href="https://example.com">a link</a>.</p>
                <script>console.log("tracking");</script>
                <iframe src="https://youtube.com/embed/test"></iframe>
                <p>Line with break<br>continues here</p>
                <span>Remove this span</span>
                <p></p>
                <ul><li>List item</li></ul>
            </div>
        ';
        
        $expected_parts = [
            'This is a paragraph with <strong>bold text</strong>.',
            'Another paragraph with <a href="https://example.com">a link</a>.',
            "Line with break\ncontinues here",
            'Remove this span',
            '<ul><li>List item</li></ul>',
        ];
        
        $result = $this->simulateFeedContentCleaning($complex_content);
        
        // Check that expected content is preserved
        foreach ($expected_parts as $part) {
            $this->assertStringContainsString($part, $result, "Complex content should contain: '{$part}'");
        }
        
        // Check that unwanted content is removed
        $this->assertStringNotContainsString('<style>', $result, 'Style tags should be removed');
        $this->assertStringNotContainsString('<script>', $result, 'Script tags should be removed');
        $this->assertStringNotContainsString('<iframe>', $result, 'Iframe tags should be removed');
        $this->assertStringNotContainsString('<div>', $result, 'Div tags should be removed');
        $this->assertStringNotContainsString('<p></p>', $result, 'Empty paragraphs should be removed');
    }

    /**
     * Simulate the feed content cleaning logic without WordPress dependencies
     */
    private function simulateFeedContentCleaning($content)
    {
        // Remove iframes (including content inside and self-closing)
        $content = preg_replace('/<iframe[^>]*(?:\/>|>.*?<\/iframe>)/s', '', $content);
        
        // Remove style tags with content (including attributes)
        $content = preg_replace('/<style[^>]*>.*?<\/style>/s', '', $content);
        
        // Remove script tags with content (including attributes)
        $content = preg_replace('/<script[^>]*>.*?<\/script>/s', '', $content);
        
        // Convert all <br> variants to newlines
        $content = preg_replace('/<br\s*\/?>/i', PHP_EOL, $content);
        
        // Strip tags except allowed ones
        $allowed_tags = '<p>,<a>,<ul>,<ol>,<li>,<strong>,<em>,<h2>,<h3>,<h4>,<h5>,<label>';
        $content = strip_tags($content, $allowed_tags);
        
        // Remove empty paragraphs
        $content = str_replace('<p></p>', '', $content);
        
        // Trim whitespace
        $content = trim($content);
        
        return $content;
    }
}
