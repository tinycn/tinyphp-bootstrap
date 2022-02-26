<?php
/**
 *
 * @copyright (C), 2013-, King.
 * @name UIViewTemplatePlugin.php
 * @author King
 * @version stable 1.0
 * @Date 2017年3月12日下午2:05:36
 * @Class List
 * @Function List
 * @History King 2021年11月16日下午5:40:10 0 第一次建立该文件
 *          King 2021年11月16日下午5:40:10 1 修改
 *          King 2021年11月16日下午5:40:10 stable 1.0.01 审定
 */

namespace Tiny\MVC\View\UI;

use Tiny\MVC\View\Engine\Template;
use Tiny\MVC\View\Engine\Template\TemplatePluginInterface;

/**
 * 
 * View Template 插件
 * 
 * @package Tiny.MVC.View.UI
 * @since  King 2021年11月16日下午5:40:10 
 * @final  King 2021年11月16日下午5:40:10 
 *
 */
class UIViewTemplatePlugin implements TemplatePluginInterface
{    
    /**
     * 可解析的标签列表
     * @var array
     */
    const PARSE_TAG_LIST = ['ui.lib', 'ui.jslib'];
    
    /**
     * 默认插入前端库的位置为头部标签最下
     * 
     * @var string
     */
    const UI_FRONTEND_INJECT_DEFAULT = 'head';
    
    /**
     * 默认插入前端库的位置列表
     * 
     * @var array
     */
    const UI_FRONTEND_INJECT_LIST = ['head', 'body'];
    
    /**
     * 当前template实例
     *
     * @var Template
     */
    protected $_template;
    
    /**
     * 当前URL插件的配置数组
     *
     * @var array
     */
    protected $_templateConfig;
    
    /**
     * 是否自动注入前端库
     * 
     * @var boolean
     */
    protected $_inject = self::UI_FRONTEND_INJECT_DEFAULT;
    
    /**
     * 是否注入
     * 
     * @var boolean
     */
    protected $_isOnceInjected = FALSE;
    
    /**
     * 前端库的URL公开地址前缀
     * 
     * @var string
     */
    protected $_publicPath = '/';
    
    /**
     * 是否为开发模式
     * @var boolean
     */
    protected $_isDev = FALSE;
    
    /**
     * 开发模式下的公共地址
     * 
     * @var string
     */
    protected $_devPublicPath;
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\Template\TemplatePluginInterface::setTemplateConfig()
     */
    public function setTemplateConfig(Template $template, array $config)
    {
        print_r($config);
        $this->_template = $template;
        $this->_templateConfig = $config;
        if (isset($config['public_path']))
        {
            $this->_publicPath = (string)$config['public_path'];
        }
        // 兼容开发模式
        $this->_isDev = (bool)$config['dev_enabled'];
        if ($this->_isDev && isset($config['dev_public_path']))
        {
            $this->_devPublicPath = (string)$config['dev_public_path'];
        }
        if (!isset($config['inject']))
        {
            return;
        }
        if ($config['inject'])
        {
            $inject = (string)$config['inject'];
            $inject = in_array($inject, self::UI_FRONTEND_INJECT_LIST) ? $inject : self::UI_FRONTEND_INJECT_DEFAULT;
            $this->_inject = $inject;
        }
        else
        {
            $this->_inject = FALSE;
        }
    }
    
    /**
     * 解析前发生
     *
     * @param string $template 解析前的模板字符串
     * @return FALSE|string
     */
    public function onPreParse($template)
    {
        return FALSE;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\Template\TemplatePluginInterface::onParseCloseTag()
     */
    public function onParseCloseTag($tagName)
    {
        if(!in_array($tagName, self::PARSE_TAG_LIST))
        {
            return FALSE;
        }
        return '';
    }

    /**
     *
     * {@inheritDoc}
     * @see \Tiny\MVC\View\Engine\Template\TemplatePluginInterface::onParseTag()
     */
    public function onParseTag($tagName, $tagBody, $extra = NULL)
    {
        if(!in_array($tagName, self::PARSE_TAG_LIST))
        {
            return FALSE;
        }
        switch ($tagName)
        {
            case 'ui.lib':
                return $this->_parseTagUILibraryTag();
        }
        
        return;
        $paramText = explode(',', $tagBody);
        $params = [];
        $isRewrite = ($extra == 'r') ? TRUE : FALSE;
        foreach($paramText as $ptext)
        {
            $ptext = trim($ptext);
            if(preg_match('/\s*(.+?)\s*=\s*(.*)\s*/i', $ptext, $out))
            {
                $params[$out[1]] = $out[2];
            }
        }
        $router = \Tiny\Tiny::getApplication()->getRouter();
        if($router)
        {
            return $router->rewriteUrl($params, $isRewrite);
        }
        return '';
    }
    
    /**
     * 解析后发生
     *
     * @param string $template 解析后的模板字符串
     * @return FALSE|string
     */
    public function onPostParse($template)
    {
        if (!$this->_inject)
        {
            return FALSE;
        }
        
        $injectTag = ($this->_inject === 'body') ? '</body>' : '</head>';
        if (strpos($template, $injectTag) === FALSE)
        {
            return FALSE;
        }   
        $libraryTag = $this->_parseTagUILibraryTag();
        $count = 1;
  
        return str_replace($injectTag, $libraryTag ."\n". $injectTag, $template, $count);
    }
    
    /**
     * 解析UI library库标签
     * 
     * @return string
     */
    protected function _parseTagUILibraryTag()
    {   
        $lib = $this->_isDev 
            ? sprintf('<script src="%s"></script>', $this->_devPublicPath)
            : sprintf('<link href="%scss/tinyphp-ui.min.css" rel="stylesheet"/><script src="%sjs/tinyphp-ui.min.js"></script>', $this->_publicPath, $this->_publicPath);
        
        return  <<<EOT
        <?php
        if (!\$this->__tinyphpUILibraryInjected) 
        {
            \$this->__tinyphpUILibraryInjected = TRUE;
            echo '{$lib}';
        }
        ?>
        EOT;  
    }
}
?>