<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    UICmsMacro.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Macros;

use Devrun\CmsModule\DeprecationException;
use Devrun\CmsModule\Utils\Common;
use Devrun\Utils\Arrays;
use Latte\CompileException;
use Latte\Compiler;
use Latte\Helpers;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;
use Nette\Utils\JsonException;

class UICmsMacros extends MacroSet
{

    public static function install(Compiler $compiler)
    {
        $set = new static($compiler);
        $set->addMacro('section', array($set, 'macroSection'), array($set, 'macroEndSection'), array($set, 'macroAttrSection'));
        $set->addMacro('article', array($set, 'macroArticle'), null, [$set, 'macroAttrArticle']);
    }

    /**
     * @internal @example
     *
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string|null
     * @throws CompileException
     */
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


    /**
     * @internal @example
     *
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string|null
     * @throws CompileException
     */
    public function macroAttrSection(MacroNode $node, PhpWriter $writer)
    {
        if (self::isAdminRequest()) {
            return $writer->write('echo \' class="\', %escape("aspekt"), \'"\'');
        }
        return null;
    }

    /**
     * @internal @example
     *
     * @param MacroNode $node
     * @param PhpWriter $writer
     */
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
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @param $namespace
     * @param $source
     * @param string $params
     * @param null $tag
     * @return string
     */
    private function writeCommand(MacroNode $node, PhpWriter $writer, $namespace, $source, string $params, $tag = null)
    {
        $params = ltrim($params, "[");
        $params = rtrim($params, "]");
        $addParam = "'type' => " . (self::isTagOfBlock($tag) ? 'block' : 'inline');
        if ($tag) $addParam .= ", 'tag' => $tag";
        $params   = rtrim(substr_replace($params, "$addParam, ", 0, 0), ", ");
        $outParams = "{$writer->formatWord($namespace)},{$writer->formatWord($source)}," . $writer->formatArray(new \Latte\MacroTokens($params));

        $command = '$_articlePipe';
        $command .= '->getArticle(' . $outParams . ')';
        $command .= "->$source";

        return $command;
    }


    /**
     * {article key |modifiers}
     *
     * @example {article layout.text1 header}
     * @example {article h2 layout.text1 header}
     * @example {article section layout.text1 header class=>"text-primary", id=>"title"}
     * @example {article section layout.text1 header class=>"text-primary", id=>"title"}
     * @example {article section layout.text1 header package=>true, page=>false, route=>true, class=>"text-primary", id=>"title"}
     * @example {article section layout.text1 header package=>true, data-toolbar=>'["heading", "|", "bold", "italic", "link", "|"]'}
     *
     * @param MacroNode $node
     * @param PhpWriter $writer
     *
     * @return string
     * @throws CompileException
     * @throws JsonException
     */
    public function macroArticle(MacroNode $node, PhpWriter $writer)
    {
        $tag = $namespace = $source = null;

        // tag or namespace
        if (!$word = $node->tokenizer->fetchWord()) {
            throw new CompileException('Required arguments are not ready {article [tag] namespace source ...} ' . $node->getNotation());
        }

        if (strpos($word, '.'))
            $namespace = $word;
        else
            $tag = $word;

        // namespace or source
        if (!$word = $node->tokenizer->fetchWord()) {
            throw new CompileException('Required arguments are not ready {article [tag] namespace source ...} ' . $node->getNotation());
        }

        if (!$namespace && !strpos($word, '.')) {
            throw new CompileException('Namespace must have two words [namespace.name]' . $node->getNotation());
        }

        if (!$namespace)
            $namespace = $word;
        else
            $source = $word;

        if (!$source) {
            if (!$source = $node->tokenizer->fetchWord()) {
                throw new CompileException('Source is required in macro [header|subHeader|content...] ' . $node->getNotation());
            }
        }
        if (!in_array($source, ['header', 'subHeader', 'perex', 'content', 'description'])) {
            throw new CompileException('Source is required in macro [header|subHeader|content...] ' . $node->getNotation());
        }

        $params = ($writer->formatArray());

        $arrayAttributes = $this->getArrayAttributes($params);
        $elementAttributes = $this->getElementAttributes($arrayAttributes);

        $modifiers = Helpers::removeFilter($node->modifiers, 'noescape') ? '%modify' : '%escape';

        if (self::isAdminRequest()) {
            $editorAttributes = $this->getEditorAttributes($arrayAttributes);
            $mergeAttributes = $elementAttributes + $editorAttributes;
            $mergeAttributes['class'] = isset($mergeAttributes['class']) ? "editor " . $mergeAttributes['class'] : 'editor';
            $dataAttributes = array_diff_key($arrayAttributes, $mergeAttributes);

            if (!$tag) $tag = 'article';
            if ($tagBlock = self::isTagOfBlock($tag)) {
                $out = 'echo "';
                $out .= "<$tag";
                $out .= " data-namespace='$namespace'";
                $out .= " data-source='$source'";
                $out .= " data-tag='$tag'";
                $out .= " data-content-type='block'";
                foreach ($mergeAttributes as $key => $attribute) {
                    $out .= is_scalar($attribute) ? " $key='$attribute'" : " $key='" . '[\"' . implode('\", \"', $attribute) . '\"]\'';
                }
                foreach ($dataAttributes as $key => $attribute) {
                    if (is_bool($attribute)) $out .= " data-$key='" . ($attribute ? 'true' : 'false') . "'";
                    elseif (is_scalar($attribute)) $out .= " data-$key='$attribute'";
                    else $out .= " data-$key='" . implode(", ", $attribute) . "'";
                }
                $out .= ">";
                $out .= '";';

                $out .= 'echo ';
                $out .= "$modifiers(" . $writer->formatWord($this->writeCommand($node, $writer, $namespace, $source, $params, $tag)) . ')';
                $out .= ';';

                $out .= 'echo "';
                $out .= "</$tag>";
                $out .= '";';

            } else {
                $editorAttributes['class'] = isset($editorAttributes['class']) ? "editor " . $editorAttributes['class'] : 'editor';

                $out = 'echo "';
                $out .= "<article";
                $out .= " data-namespace='$namespace'";
                $out .= " data-source='$source'";
                $out .= " data-tag='$tag'";
                $out .= " data-content-type='inline'";
                foreach ($editorAttributes as $key => $attribute) {
                    $out .= is_scalar($attribute) ? " $key='$attribute'" : " $key='" . '[\"' . implode('\", \"', $attribute) . '\"]\'';
                }
                foreach ($dataAttributes as $key => $attribute) {
                    if (is_bool($attribute)) $out .= " data-$key='" . ($attribute ? 'true' : 'false') . "'";
                    elseif (is_scalar($attribute)) $out .= " data-$key='$attribute'";
                    else $out .= " data-$key='" . implode(", ", $attribute) . "'";
                }
                $out .= ">";

                $out .= "<$tag";
                foreach ($elementAttributes as $key => $attribute) {
                    $out .= is_scalar($attribute) ? " $key='$attribute'" : " $key='" . '[\"' . implode('\", \"', $attribute) . '\"]\'';
                }
                $out .= ">";
                $out .= '";';

                $out .= 'echo ';
                $out .= "$modifiers(" . $writer->formatWord($this->writeCommand($node, $writer, $namespace, $source, $params, $tag)) . ')';
                $out .= ';';

                $out .= 'echo "';
                $out .= "</$tag>";

                $out .= "</article>";
                $out .= '";';
            }

        } else {
            if ($tag) {
                $out = 'echo "';
                $out .= "<$tag";
                foreach ($elementAttributes as $key => $attribute) {
                    $out .= " $key='$attribute'"; // fastest method
                    // $out .= is_scalar($attribute) ? " $key='$attribute'" : " $key='" . '[\"' . implode('\", \"', $attribute) . '\"]\'';
                }
                $out .= ">";
                $out .= '";';

                $out .= 'echo ';
                $out .= "$modifiers(" . $writer->formatWord($this->writeCommand($node, $writer, $namespace, $source, $params, $tag)) . ')';
                $out .= ';';

                $out .= 'echo "';
                $out .= "</$tag>";
                $out .= '";';

            } else {
                $out = 'echo ';
                $out .= "$modifiers(" . $writer->formatWord($this->writeCommand($node, $writer, $namespace, $source, $params)) . ')';
                $out .= ';';
            }
        }

        return $writer->write($out);
    }


    /**
     * tag n:article key
     * @deprecated use macroArticle only
     *
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string|null
     * @throws CompileException
     */
    public function macroAttrArticle(MacroNode $node, PhpWriter $writer)
    {
        throw new DeprecationException("use {article layout.text1 header}");
        if (!$namespace = $node->tokenizer->fetchWord()) {
            throw new CompileException('Namespace is required in macro ' . $node->getNotation());
        }

        if (!strpos($namespace, '.')) {
            throw new CompileException('Namespace must have two words [namespace.name]' . $node->getNotation());
        }

        if (!$source = $node->tokenizer->fetchWord()) {
            throw new CompileException('Source is required in macro ' . $node->getNotation());
        }

        $params = ($writer->formatArray());
        $params = ltrim($params, "[");
        $params = rtrim($params, "]");

        $addParam = "'type' => " . (self::isTagOfBlock($node->htmlNode->name) ? 'outline' : 'inline');
        $params   = rtrim(substr_replace($params, "$addParam, ", 0, 0), ", ");

        $outParams = "{$writer->formatWord($namespace)},{$writer->formatWord($source)}," . $writer->formatArray(new \Latte\MacroTokens($params));
        $node->htmlNode->attrs = ['article' => $outParams, 'namespace' => $namespace, 'source' => $source];

        // emulate only admin, we have not services dependency
        if (self::isAdminRequest()) {

//            $out = 'echo " contenteditable=\"true\"';


            $out = 'echo "';
            $out .= ' data-namespace=\"' . $namespace . '\"';
            $out .= ' data-source=\"' . $source . '\"';
            // $out .= ' data-route=\"' . "\$presenter->request->getParameter('routeId')" . '\"';

            // replace input array keys to data-key
            $modify = preg_replace("%'*(\w*)'*\s*=>\s*(\w+)%", 'data-$1=\\"$2\\"', $params);
            $out .= $modify;
            $out .= '"';

            return $writer->write($out);
        }

        return null;
    }

    /**
     * @param string $tag
     * @return bool
     */
    public static function isTagOfBlock(?string $tag = 'article'): bool
    {
        return in_array($tag, [
            'address', 'article', 'aside', 'blockquote', 'canvas', 'div', 'dl', 'dt', 'fieldset', 'figure', 'footer', 'header',
            'main', 'nav', 'ol', 'ul', 'section', 'table', 'ul'
        ]);
    }


    /**
     * @return bool is request from admin page
     */
    public static function isAdminRequest()
    {
        return Common::isAdminRequest();
    }


    /**
     * @param $attributes
     * @return array|null
     * @throws JsonException
     */
    private function getElementAttributes(array $attributes): ?array
    {
        unset($attributes['page'], $attributes['package'], $attributes['route'], $attributes['data-toolbar'],);
        return $attributes;
    }

    /**
     * @param $attributes
     * @return array|null
     * @throws JsonException
     */
    private function getEditorAttributes(array $attributes): ?array
    {
        $result = [];
        if (isset($attributes['data-toolbar'])) $result['data-toolbar'] = $attributes['data-toolbar'];
        return $result;
    }


    /**
     * @param string $params
     * @return array|null
     * @throws JsonException
     */
    private function getArrayAttributes(string $params): ?array
    {
        return Arrays::stringArrayToArray($params);
    }


}