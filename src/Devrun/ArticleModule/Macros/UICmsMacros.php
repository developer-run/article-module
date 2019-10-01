<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    UICmsMacro.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Macros;

use Devrun\CmsModule\Utils\Common;
use Latte\CompileException;
use Latte\Compiler;
use Latte\Helpers;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;
use Tracy\Debugger;

class UICmsMacros extends MacroSet
{

    public static function install(Compiler $compiler)
    {
        $set = new static($compiler);
        $set->addMacro('section', array($set, 'macroSection'), array($set, 'macroEndSection'), array($set, 'macroAttrSection'));
        $set->addMacro('article', array($set, 'macroArticle'), null, [$set, 'macroAttrArticle']);

    }


    public function macroSection(MacroNode $node, PhpWriter $writer)
    {
        // emulate only admin, we have not services dependency
        if (self::isAdminRequest()) {
            $tag = 'div';
            if (!$node->prefix) {
                return $writer->write("?>\n<$tag class=\"<?php echo htmlSpecialChars(\"aspekt\") ?>\"><?php ");
            }
        }

        return null;
    }


    public function macroAttrSection(MacroNode $node, PhpWriter $writer)
    {
        if (self::isAdminRequest()) {
            return $writer->write('echo \' class="\', %escape("aspekt"), \'"\'');
        }
        return null;
    }


    public function macroEndSection(MacroNode $node, PhpWriter $writer)
    {
        if (self::isAdminRequest()) {
            if (!$node->prefix) {
                $tag = 'div';
                $node->closingCode .= "\n</$tag>";
            }
        }
    }


    /**
     * {article key |modifiers}
     *
     * @param MacroNode $node
     * @param PhpWriter $writer
     *
     * @return string
     * @throws CompileException
     */
    public function macroArticle(MacroNode $node, PhpWriter $writer)
    {
        if (!isset($node->htmlNode->attrs['article'])) {
            throw new CompileException($node->getNotation() . " required attribute [<h3 n:article='namespace ...']");
        }

        $parentArgs = $node->htmlNode->attrs['article'];
        $namespace = $node->htmlNode->attrs['namespace'];
        $source = $node->htmlNode->attrs['source'];

        if (!$namespace || !$source) {
            throw new CompileException('Namespace and source is required in macro ' . $node->getNotation());
        }

        $noEscape = Helpers::removeFilter($node->modifiers, 'noescape');

        $command = '$_articlePipe';
        $command .= '->getArticle(' . $parentArgs . ')';
        $command .= "->$source";

        return $noEscape
            ? $writer->write('echo %modify(' . $writer->formatWord($command) . ')')
            : $writer->write('echo %escape(' . $writer->formatWord($command) . ')');
    }


    public function macroAttrArticle(MacroNode $node, PhpWriter $writer)
    {
        if (!$namespace = $node->tokenizer->fetchWord()) {
            throw new CompileException('Namespace is required in macro ' . $node->getNotation());
        }

        if (!strpos($namespace, '.')) {
            throw new CompileException('Namespace must have two words [namespace.name]' . $node->getNotation());
        }

        if (!$source = $node->tokenizer->fetchWord()) {
            throw new CompileException('Source is required in macro ' . $node->getNotation());
        }

        $params = '';
        if ($editor = $node->tokenizer->fetchWord()) {
            $params .= "editor=>$editor,";
            $params .= "type=>" . ($node->htmlNode->name == 'div' ? 'outline' : 'inline');
        }

        $outParams = "{$writer->formatWord($namespace)},{$writer->formatWord($source)}," . $writer->formatArray(new \Latte\MacroTokens($params));
        $node->htmlNode->attrs = ['article' => $outParams, 'namespace' => $namespace, 'source' => $source];

        // emulate only admin, we have not services dependency
        if (self::isAdminRequest()) {

            $out = 'echo " contenteditable=\"true\"';
            $out .= ' data-namespace=\"' . $namespace . '\"';
            $out .= ' data-source=\"' . $source . '\"';
//            $out .= ' data-route=\"' . "\$presenter->request->getParameter('routeId')" . '\"';

            if ($editor) {
                $out .= ' data-editor=\"' .$editor. '\"';
            }
            $out .= '"';


            return $writer->write($out);
        }

        return null;
    }



    /**
     * @return bool is request from admin page
     */
    public static function isAdminRequest()
    {
        return Common::isAdminRequest();
    }

}