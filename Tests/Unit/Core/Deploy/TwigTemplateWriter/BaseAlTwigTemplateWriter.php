<?php
/**
 * This file is part of the RedKiteCmsBunde Application and it is distributed
 * under the GPL LICENSE Version 2.0. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) RedKite Labs <webmaster@redkite-labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.redkite-labs.com
 *
 * @license    GPL LICENSE Version 2.0
 *
 */

namespace RedKiteLabs\RedKiteCmsBundle\Tests\Unit\Core\Deploy\TwigTemplateWriter;

use RedKiteLabs\RedKiteCmsBundle\Tests\TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * AlTwigTemplateWriterPagesTest
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 */
abstract class BaseAlTwigTemplateWriter extends TestCase
{
    protected $pageTree;
    protected $urlManager;
    protected $template;

    protected function setUp()
    {
        parent::setUp();

        $this->pageTree = $this->getMockBuilder('RedKiteLabs\RedKiteCmsBundle\Core\PageTree\AlPageTree')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        
        $this->templateSlots = $this->getMock('RedKiteLabs\ThemeEngineBundle\Core\TemplateSlots\AlTemplateSlotsInterface');
        
        $this->template = $this->getMockBuilder('RedKiteLabs\ThemeEngineBundle\Core\Template\AlTemplate')
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->template->expects($this->any())
            ->method('getTemplateSlots')
            ->will($this->returnValue($this->templateSlots));
        
        $this->template->expects($this->any())
            ->method('getTemplateName')
            ->will($this->returnValue('home'));

        $this->pageTree->expects($this->exactly(2))
            ->method('getTemplate')
            ->will($this->returnValue($this->template));

        $this->pageTree->expects($this->any())
            ->method('getAlPage')
            ->will($this->returnValue($this->setUpPage('index')));

        $this->pageTree->expects($this->any())
            ->method('getAlLanguage')
            ->will($this->returnValue($this->setUpLanguage('en')));
        
        $this->viewRenderer = $this->getMockBuilder('RedKiteLabs\RedKiteCmsBundle\Core\ViewRenderer\AlViewRenderer')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        
        $this->urlManager = $this->getMock('\RedKiteLabs\RedKiteCmsBundle\Core\UrlManager\AlUrlManagerInterface');
        $this->urlManager->expects($this->any())
            ->method('fromUrl')
            ->will($this->returnSelf());

        $this->blockManagerFactory = $this->getMock('\RedKiteLabs\RedKiteCmsBundle\Core\Content\Block\AlBlockManagerFactoryInterface');
        
        $this->deployBundle = "AcmeWebsiteBundle";
        $this->templatesFolder = 'RedKiteCms';

        $this->root = vfsStream::setup('root');
    }

    protected function setUpPage($pageName)
    {
        $page = $this->getMock('RedKiteLabs\RedKiteCmsBundle\Model\AlPage');
        $page->expects($this->any())
            ->method('getPageName')
            ->will($this->returnValue($pageName));
        
        $page->expects($this->any())
            ->method('getTemplateName')
            ->will($this->returnValue('home'));

        return $page;
    }

    protected function setUpLanguage($languageName)
    {
        $language = $this->getMock('RedKiteLabs\RedKiteCmsBundle\Model\AlLanguage');
        $language->expects($this->any())
            ->method('getLanguageName')
            ->will($this->returnValue($languageName));

        return $language;
    }

    protected function setUpPageBlocks(array $blocks = null, $yuiEnabled = true)
    {
        if (null === $blocks) {
            $blocks = array("logo" => array($this->setUpBlock('logo')));
        }

        $pageBlocks = $this->getMockBuilder('RedKiteLabs\RedKiteCmsBundle\Core\Content\PageBlocks\AlPageBlocks')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $pageBlocks->expects($this->any())
            ->method('getBlocks')
            ->will($this->returnValue($blocks));

        $this->pageTree->expects($this->once())
            ->method('getPageBlocks')
            ->will($this->returnValue($pageBlocks));
    }

    protected function setUpMetatagsAndAssets($title, $description, $keywords, $externalStylesheets, $externalJavascripts, $internalStylesheets, $internalJavascripts)
    {
        $this->pageTree->expects($this->any())
                ->method('__call')
                ->will($this->onConsecutiveCalls($title, $description, $keywords, $externalStylesheets, $externalJavascripts, $internalStylesheets, $internalJavascripts));
    }

    protected function setUpTemplateSlots($slots = array())
    {
        $this->template->expects($this->once())
            ->method('getSlots')
            ->will($this->returnValue($slots));
        
        if (empty($slots)) {
            return;
        }
        
        $alSlots = array();
        foreach($slots as $slotName => $slotAttributes) {
            $slot = $this->getMockBuilder('RedKiteLabs\ThemeEngineBundle\Core\ThemeSlots\AlSlot')
                    ->disableOriginalConstructor()
                    ->getMock();
            
            $slot->expects($this->any())
                ->method('getRepeated')
                ->will($this->returnValue($slotAttributes['repeated']));
            
            $slot->expects($this->any())
                ->method('getSlotName')
                ->will($this->returnValue($slotName));

            $alSlots[] = $slot;            
        }
        
        $this->template->expects($this->any())
                ->method('getSlot')
                ->will(new \PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls($alSlots));
    }

    protected function setUpBlock($slotName)
    {
        $block = $this->getMock('RedKiteLabs\RedKiteCmsBundle\Model\AlBlock');
        
        $block->expects($this->any())
            ->method('getSlotName')
            ->will($this->returnValue($slotName));
        
        return $block;
    }

    protected function setUpBlockManager($deployContent = 'Formatted content for deploying', $callingTimes = 1)
    {
        $blockManager = $this->getMockBuilder('RedKiteLabs\RedKiteCmsBundle\Core\Content\Block\AlBlockManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        if ($callingTimes > 0) {
            $blockManager->expects($this->exactly($callingTimes))
                ->method('setEditorDisabled')
                ->with(true);
        }
        
        if (null !== $deployContent) {
            $this->viewRenderer
                ->expects($this->exactly($callingTimes))
                ->method('render')
                ->will($this->returnValue($deployContent))
            ;
        }
        
        return $blockManager;
    }

    protected function setUpBlockManagerFactory($deployContent = 'Formatted content for deploying', $callingTimes = 0)
    {
        $blockManager = $this->setUpBlockManager($deployContent, $callingTimes);

        $this->blockManagerFactory->expects($this->exactly($callingTimes))
            ->method('createBlockManager')
            ->will($this->returnValue($blockManager));
    }
    
    protected function addSomeLove()
    {
        $content = "{% block internal_header_stylesheets %}" . PHP_EOL;
        $content .= "  {{ parent() }}" . PHP_EOL . PHP_EOL;
        $content .= "  <style>.al-credits{width:100%;background-color:#fff;text-align:center;padding:6px;border-top:1px solid #000;margin-top:1px;}.al-credits a{color:#333;}.al-credits a:hover{color:#C20000;}</style>" . PHP_EOL;
        $content .= "{% endblock %}" . PHP_EOL . PHP_EOL;
        $content .= "{% block body %}" . PHP_EOL;
        $content .= "  {{ parent() }}" . PHP_EOL . PHP_EOL;
        $content .= "  <div class=\"al-credits\"><a href=\"http://redkite-labs.com\">Powered by RedKiteCms</div>" . PHP_EOL;
        $content .= "{% endblock %}" . PHP_EOL;
        
        return $content;
    }
}
