<?php
/**
 * This file is part of the RedKiteCmsBunde Application and it is distributed
 * under the MIT License. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) RedKite Labs <webmaster@redkite-labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.redkite-labs.com
 *
 * @license    MIT License
 *
 */

namespace RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Tests\Unit\Controller;

use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Tests\TestCase;
use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Controller\ThemesController;


/**
 * ThemesControllerTest
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 */
class ThemesControllerTest extends TestCase
{
    private $request;
    private $templateManager;
    private $theme;
    private $themes;
    private $templating;
    private $container;
    private $controller;
    private $themeName = 'BootbusinessThemeBundle';

    protected function setUp()
    {
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->request
             ->expects($this->once())
             ->method('get')
             ->will($this->returnValue($this->themeName));
        
        $this->siteBootstrap = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\SiteBootstrap\SiteBootstrapInterface');
        $this->pageBlocks = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\PageBlocks\PageBlocksInterface');
        
        $themeSlots = $this->getMock('RedKiteLabs\ThemeEngineBundle\Core\ThemeSlots\ThemeSlotsInterface');
        
        $this->template = $this->getMockBuilder('RedKiteLabs\ThemeEngineBundle\Core\Template\Template')
             ->disableOriginalConstructor()
             ->getMock()
        ;
        
        $this->templateManager = $this->getMockBuilder('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Content\Template\TemplateManager')
                 ->disableOriginalConstructor()
                 ->getMock()
        ;
        
        $this->templateManager
             ->expects($this->once())
             ->method('refresh')
             ->with($themeSlots, $this->template, $this->pageBlocks)
        ;
        
        $this->theme = $this->getMockBuilder('RedKiteLabs\ThemeEngineBundle\Core\Theme\Theme')
             ->disableOriginalConstructor()
             ->getMock()
        ;
        
        $this->theme
             ->expects($this->once())
             ->method('getHomeTemplate')
             ->will($this->returnValue($this->template))
        ;
        
        $this->theme
            ->expects($this->once())
            ->method('getThemeSlots')
            ->will($this->returnValue($themeSlots))
        ;
        
        $this->themes = $this->getMock('RedKiteLabs\ThemeEngineBundle\Core\ThemesCollection\ThemesCollection');
        $this->themes
             ->expects($this->once())
             ->method('getTheme')
             ->will($this->returnValue($this->theme))
        ;
        
        $this->response = $this->getMock('Symfony\Component\HttpFoundation\Response');      
        $this->templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $this->templating
             ->expects($this->once())
             ->method('renderResponse')
             ->will($this->returnValue($this->response))
        ;
        
        $this->translator = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Translator\TranslatorInterface');
        $this->translator
             ->expects($this->any())
             ->method('translate')
             //->will($this->returnValue($this->response))
        ;
        
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        
        $this->controller = new ThemesController();
        $this->controller->setContainer($this->container);
    }

    /**
     * @dataProvider resultProvider
     */
    public function testStartNewTheme($result)
    {
        $this->initContainer();
        $this->siteBootstrap
             ->expects($this->once())
             ->method('setTemplateManager')
             ->will($this->returnSelf());
        
        $this->siteBootstrap
             ->expects($this->once())
             ->method('bootstrap')
             ->will($this->returnValue($result));
         
        $this->siteBootstrap
             ->expects($this->once())
             ->method('getErrorMessage');
         
        $sequence = 5;
        if ($result) {
            
            $activeTheme = $this->getMock('RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\ActiveTheme\ActiveThemeInterface');
            $activeTheme
                 ->expects($this->once())
                 ->method('writeActiveTheme')
                 ->with($this->themeName)
            ;
            
            $this->container
                 ->expects($this->at($sequence))
                 ->method('get')
                 ->with('red_kite_cms.active_theme')
                 ->will($this->returnValue($activeTheme));
            $sequence++;
            
            $this->container
                ->expects($this->at($sequence))
                 ->method('get')
                 ->with('red_kite_cms.translator')
                 ->will($this->returnValue($this->translator)); 
            $sequence++;    
        }
        
        $this->container
             ->expects($this->at($sequence))
             ->method('get')
             ->with('templating')
             ->will($this->returnValue($this->templating));
                        
        $response = $this->controller->startFromThemeAction();
        $this->assertEquals($this->response, $response);
    }
    
    public function resultProvider()
    {
        return array(
            array(false),
            array(true),
        );
    }
       
    private function initContainer()
    {
        $this->container
             ->expects($this->at(0))
             ->method('get')
             ->with('request')
             ->will($this->returnValue($this->request));

        $this->container
             ->expects($this->at(1))
             ->method('get')
             ->with('red_kite_labs_theme_engine.themes')
             ->will($this->returnValue($this->themes));

        $this->container
             ->expects($this->at(2))
             ->method('get')
             ->with('red_kite_cms.page_blocks')
             ->will($this->returnValue($this->pageBlocks))
        ;
        
        $this->container
             ->expects($this->at(3))
             ->method('get')
             ->with('red_kite_cms.template_manager')
             ->will($this->returnValue($this->templateManager));
                
        $this->container
             ->expects($this->at(4))
             ->method('get')
             ->with('red_kite_cms.site_bootstrap')
             ->will($this->returnValue($this->siteBootstrap));
    }
}
