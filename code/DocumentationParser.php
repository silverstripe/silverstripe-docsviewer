<?php

/**
 * Parser wrapping the Markdown Extra parser.
 * 
 * @see http://michelf.com/projects/php-markdown/extra/
 *
 * @package docsviewer
 */
class DocumentationParser
{
    const CODE_BLOCK_BACKTICK = 1;
    const CODE_BLOCK_COLON = 2;

    /**
     * @var array
     */
    public static $heading_counts = array();
    
    /**
     * Parse a given path to the documentation for a file. Performs a case 
     * insensitive lookup on the file system. Automatically appends the file 
     * extension to one of the markdown extensions as well so /install/ in a
     * web browser will match /install.md or /INSTALL.md.
     * 
     * Filepath: /var/www/myproject/src/cms/en/folder/subfolder/page.md
     * URL: http://myhost/mywebroot/dev/docs/2.4/cms/en/folder/subfolder/page
     * Webroot: http://myhost/mywebroot/
     * Baselink: dev/docs/2.4/cms/en/
     * Pathparts: folder/subfolder/page
     * 
     * @param DocumentationPage $page
     * @param String $baselink Link relative to webroot, up until the "root" 
     *							of the module. Necessary to rewrite relative 
     *							links
     *
     * @return String
     */
    public static function parse(DocumentationPage $page, $baselink = null)
    {
        if (!$page || (!$page instanceof DocumentationPage)) {
            return false;
        }

        $md = $page->getMarkdown(true);
        
        // Pre-processing
        $md = self::rewrite_image_links($md, $page);
        $md = self::rewrite_relative_links($md, $page, $baselink);

        $md = self::rewrite_api_links($md, $page);
        $md = self::rewrite_heading_anchors($md, $page);

        $md = self::rewrite_code_blocks($md);

        $parser = new ParsedownExtra();
        $parser->setBreaksEnabled(false);

        $text = $parser->text($md);

        return $text;
    }
    
    public static function rewrite_code_blocks($md)
    {
        $started = false;
        $inner = false;
        $mode = false;
        $end = false;
        $debug = false;

        $lines = explode("\n", $md);
        $output = array();

        foreach ($lines as $i => $line) {
            if ($debug) {
                var_dump('Line '. ($i + 1) . ' '. $line);
            }

            // if line just contains whitespace, continue down the page.
            // Prevents code blocks with leading tabs adding an extra line.
            if (preg_match('/^\s$/', $line) && !$started) {
                continue;
            }

            if (!$started && preg_match('/^[\t]*:::\s*(.*)/', $line, $matches)) {
                // first line with custom formatting
                if ($debug) {
                    var_dump('Starts a new block with :::');
                }

                $started = true;
                $mode = self::CODE_BLOCK_COLON;

                $output[$i] = sprintf('```%s', (isset($matches[1])) ? trim($matches[1]) : "");
            } elseif (!$started && preg_match('/^\t*```\s*(.*)/', $line, $matches)) {
                if ($debug) {
                    var_dump('Starts a new block with ```');
                }

                $started = true;
                $mode = self::CODE_BLOCK_BACKTICK;

                $output[$i] = sprintf('```%s', (isset($matches[1])) ? trim($matches[1]) : "");
            } elseif ($started && $mode == self::CODE_BLOCK_BACKTICK) {
                // inside a backtick fenced box
                if (preg_match('/^\t*```\s*/', $line, $matches)) {
                    if ($debug) {
                        var_dump('End a block with ```');
                    }

                    // end of the backtick fenced box. Unset the line that contains the backticks
                    $end = true;
                } else {
                    if ($debug) {
                        var_dump('Still in a block with ```');
                    }

                    // still inside the line.
                    if (!$started) {
                        $output[$i - 1] = '```';
                    }

                    $output[$i] = $line;
                    $inner = true;
                }
            } elseif (preg_match('/^[\ ]{0,3}?[\t](.*)/', $line, $matches)) {

                // inner line of block, or first line of standard markdown code block
                // regex removes first tab (any following tabs are part of the code).
                if (!$started) {
                    if ($debug) {
                        var_dump('Start code block because of tab. No fence');
                    }

                    $output[$i - 1] = '```';
                } else {
                    if ($debug) {
                        var_dump('Content is still tabbed so still inner');
                    }
                }

                $output[$i] = $matches[1];
                $inner = true;
                $started = true;
            } elseif ($started && $inner && trim($line) === "") {
                if ($debug) {
                    var_dump('Inner line of code block');
                }

                // still inside a colon based block, if the line is only whitespace 
                // then continue with  with it. We can continue with it for now as 
                // it'll be tidied up later in the $end section.
                $inner = true;
                $output[$i] = $line;
            } elseif ($started && $inner) {
                // line contains something other than whitespace, or tabbed. E.g
                // 	> code
                //	> \n
                //	> some message
                //
                // So actually want to reset $i to the line before this new line
                // and include this line. The edge case where this will fail is
                // new the following segment contains a code block as well as it
                // will not open.
                if ($debug) {
                    var_dump('Contains something that isnt code. So end the code.');
                }

                $end = true;
                $output[$i] = $line;
                $i = $i - 1;
            } else {
                $output[$i] = $line;
            }

            if ($end) {
                if ($debug) {
                    var_dump('End of code block');
                }
                $output = self::finalize_code_output($i, $output);

                // reset state
                $started = $inner = $mode = $end = false;
            }
        }

        if ($started) {
            $output = self::finalize_code_output($i+1, $output);
        }

        return implode("\n", $output);
    }

    /**
     * Adds the closing code backticks. Removes trailing whitespace.
     *
     * @param int
     * @param array
     *
     * @return array
     */
    private static function finalize_code_output($i, $output)
    {
        if (isset($output[$i]) && trim($output[$i])) {
            $output[$i] .= "\n```\n";
        } else {
            $output[$i] = "```";
        }

        return $output;
    }
    
    public static function rewrite_image_links($md, $page)
    {
        // Links with titles
        $re = '/
			!
			\[
				(.*?) # image title (non greedy)
			\] 
			\(
				(.*?) # image url (non greedy)
			\)
		/x';
        preg_match_all($re, $md, $images);

        if ($images) {
            foreach ($images[0] as $i => $match) {
                $title = $images[1][$i];
                $url = $images[2][$i];
                
                // Don't process absolute links (based on protocol detection)
                $urlParts = parse_url($url);

                if ($urlParts && isset($urlParts['scheme'])) {
                    continue;
                }
                
                // Rewrite URL (relative or absolute)
                $baselink = Director::makeRelative(
                    dirname($page->getPath())
                );

                // if the image starts with a slash, it's absolute
                if (substr($url, 0, 1) == '/') {
                    $relativeUrl = str_replace(BASE_PATH, '', Controller::join_links(
                        $page->getEntity()->getPath(),
                        $url
                    ));
                } else {
                    $relativeUrl = rtrim($baselink, '/') . '/' . ltrim($url, '/');
                }

                // Resolve relative paths
                while (strpos($relativeUrl, '/..') !== false) {
                    $relativeUrl = preg_replace('/\w+\/\.\.\//', '', $relativeUrl);
                }
                
                // Make it absolute again
                $absoluteUrl = Controller::join_links(
                    Director::absoluteBaseURL(),
                    $relativeUrl
                );
                
                // Replace any double slashes (apart from protocol)
//				$absoluteUrl = preg_replace('/([^:])\/{2,}/', '$1/', $absoluteUrl);

                // Replace in original content
                $md = str_replace(
                    $match,
                    sprintf('![%s](%s)', $title, $absoluteUrl),
                    $md
                );
            }
        }
        
        return $md;
    }
        
     /**
     * Rewrite links with special "api:" prefix to html as in the following example:
     *
     * (1) [api:DataObject] gets re-written to 
     *     <a href="https://api.silverstripe.org/search/lookup/?q=DataObject&version=2.4&module=framework">DataObject</a>
     * (2) [api:DataObject::$defaults] gets re-written to
     *     <a href="https://api.silverstripe.org/search/lookup/?q=DataObject::$defaults&version=2.4&module=framework">DataObject::$defaults</a>
     * (3) [api:DataObject::populateDefaults()] gets re-written to
     *     <a href="https://api.silverstripe.org/search/lookup/?q=DataObject::populateDefaults()&version=2.4&module=framework">DataObject::$defaults</a>
     * (4) [Title](api:DataObject) gets re-written to 
     *     <a href="https://api.silverstripe.org/search/lookup/?q=DataObject&version=2.4&module=framework">Title</a>
     * (5) [Title](api:DataObject::$defaults) gets re-written to
     *     <a href="https://api.silverstripe.org/search/lookup/?q=DataObject::$defaults&version=2.4&module=framework">Title</a>
     * (6) [Title](api:DataObject->populateDefaults()) gets re-written to
     *     <a href="https://api.silverstripe.org/search/lookup/?q=DataObject::populateDefaults()&version=2.4&module=framework">Title</a>
     *
     *  The markdown parser gets confused by the extra pair of parentheses in links of the form [DataObject](api:DataObject->populateDefaults()) so 
     * all links are re-written as html markup instead of markdown [Title](url). This also prevents other markdown parsing problems.
     * 
     * @param String $md
     * @param DocumentationPage $page
     * @return String
     */
    public static function rewrite_api_links($md, $page)
    {
        $version = $page->getVersion();
        $module = $page->getEntity()->getKey();
 
        // define regexs of the api links to be parsed (note: exclude backticks using [^`])
        $regexs = array(
            'title_and_method' => '#[^`]\[(.*?)\]\(api:(.*?\(\))\)[^`]#',   // title_and_method = (6) (must be first)
            'title_remaining'  => '#[^`]\[(.*?)\]\(api:(.*?)\)[^`]#',       // title_and_remaining = (4) and (5)
            'no_title'         => '#[^`]\[api:(.*?)\][^`]#'                 // no_title handles = (1),(2) and (3)
        );
	
	// define output format for parsing api links without backticks into html
        $html_format = '&nbsp;<a href="http://api.silverstripe.org/search/lookup/?q=%s&version=%s&module=%s">%s</a>';

        // parse api links without backticks into html
        foreach($link_regexs as $link_type => $link_regex) {
            preg_match_all($link_regex, $md, $links);
            if($links) {
                foreach($links[0] as $i => $match) {
                    if( $regex_type === 'no_title' ){
                        $title = $links[1][$i];
                        $link = $links[1][$i];
                    } else {
                        $title = $links[1][$i];
                        $link = $links[2][$i];
                    }
                    $html = sprintf($html_format, $link, $version, $module, $title);
                    $match = substr($match,0,strlen($match)-1);
                    $md = str_replace($match,$html,$md);
                }
            } 
        }
        return $md;
    }
    
    /**
     *
     */
    public static function rewrite_heading_anchors($md, $page)
    {
        $re = '/^\#+(.*)/m';
        $md = preg_replace_callback($re, array('DocumentationParser', '_rewrite_heading_anchors_callback'), $md);
        
        return $md;
    }
    
    /**
     *
     */
    public static function _rewrite_heading_anchors_callback($matches)
    {
        $heading = $matches[0];
        $headingText = $matches[1];

        if (preg_match('/\{\#.*\}/', $headingText)) {
            return $heading;
        }

        if (!isset(self::$heading_counts[$headingText])) {
            self::$heading_counts[$headingText] = 1;
        } else {
            self::$heading_counts[$headingText]++;
            $headingText .= "-" . self::$heading_counts[$headingText];
        }

        return sprintf("%s {#%s}", preg_replace('/\n/', '', $heading), self::generate_html_id($headingText));
    }
    
    /**
     * Generate an html element id from a string
     * 
     * @return String
     */
    public static function generate_html_id($title)
    {
        $t = $title;
        $t = str_replace('&amp;', '-and-', $t);
        $t = str_replace('&', '-and-', $t);
        $t = preg_replace('/[^A-Za-z0-9]+/', '-', $t);
        $t = preg_replace('/-+/', '-', $t);
        $t = trim($t, '-');
        $t = strtolower($t);
                
        return $t;
    }
    
    /**
     * Resolves all relative links within markdown.
     * 
     * @param String $md Markdown content
     * @param DocumentationPage $page
     *
     * @return String Markdown
     */
    public static function rewrite_relative_links($md, $page)
    {
        $baselink = $page->getEntity()->Link();
        
        $re = '/
			([^\!]?) # exclude image format
			\[
				(.*?) # link title (non greedy)
			\] 
			\(
				(.*?) # link url (non greedy)
			\)
		/x';
        preg_match_all($re, $md, $matches);
        
        // relative path (relative to module base folder), without the filename.
        // For "sapphire/en/current/topics/templates", this would be "templates"
        $relativePath = dirname($page->getRelativePath());

        if (strpos($page->getRelativePath(), 'index.md')) {
            $relativeLink = $page->getRelativeLink();
        } else {
            $relativeLink = dirname($page->getRelativeLink());
        }

        if ($relativePath == '.') {
            $relativePath = '';
        }

        if ($relativeLink == ".") {
            $relativeLink = '';
        }
        
        // file base link
        $fileBaseLink = Director::makeRelative(dirname($page->getPath()));
        
        if ($matches) {
            foreach ($matches[0] as $i => $match) {
                $title = $matches[2][$i];
                $url = $matches[3][$i];
            
                // Don't process API links
                if (preg_match('/^api:/', $url)) {
                    continue;
                }
            
                // Don't process absolute links (based on protocol detection)
                $urlParts = parse_url($url);
                if ($urlParts && isset($urlParts['scheme'])) {
                    continue;
                }

                // for images we need to use the file base path
                if (preg_match('/_images/', $url)) {
                    $relativeUrl = Controller::join_links(
                        Director::absoluteBaseURL(),
                        $fileBaseLink,
                        $url
                    );
                } else {
                    // Rewrite public URL
                    if (preg_match('/^\//', $url)) {
                        // Absolute: Only path to module base
                        $relativeUrl = Controller::join_links($baselink, $url, '/');
                    } else {
                        // Relative: Include path to module base and any folders
                        $relativeUrl = Controller::join_links($baselink, $relativeLink, $url, '/');
                    }
                }
                
                // Resolve relative paths
                while (strpos($relativeUrl, '..') !== false) {
                    $relativeUrl = preg_replace('/[-\w]+\/\.\.\//', '', $relativeUrl);
                }
            
                // Replace any double slashes (apart from protocol)
                $relativeUrl = preg_replace('/([^:])\/{2,}/', '$1/', $relativeUrl);

                // Replace in original content
                $md = str_replace(
                    $match,
                    sprintf('%s[%s](%s)', $matches[1][$i], $title, $relativeUrl),
                    $md
                );
            }
        }
        
        return $md;
    }
    
    /**
     * Strips out the metadata for a page
     *
     * @param DocumentationPage
     */
    public static function retrieve_meta_data(DocumentationPage &$page)
    {
        if ($md = $page->getMarkdown()) {
            $matches = preg_match_all('/
				(?<key>[A-Za-z0-9_-]+): 
				\s*
				(?<value>.*)
			/x', $md, $meta);
        
            if ($matches) {
                foreach ($meta['key'] as $index => $key) {
                    if (isset($meta['value'][$index])) {
                        $page->setMetaData($key, $meta['value'][$index]);
                    }
                }
            }
        }
    }
}
