define("ace/theme/cerb",["require","exports","module","ace/lib/dom"], function(require, exports, module) {
"use strict";

exports.isDark = false;
exports.cssText = ".ace-cerb .ace_gutter {\
background: var(--cerb-color-background-contrast-230);\
border-right: 1px solid var(--cerb-color-background-contrast-160);\
color: var(--cerb-color-background-contrast-150);\
}\
.ace-cerb .ace_print-margin {\
width: 1px;\
background: #ebebeb;\
}\
.ace-cerb {\
background-color: var(--cerb-editor-background);\
color: var(--cerb-color-text);\
}\
.ace-cerb .ace_fold {\
background-color: var(--cerb-editor-fold);\
}\
.ace-cerb .ace_cursor {\
color: var(--cerb-color-text);\
}\
.ace-cerb .ace_storage,\
.ace-cerb .ace_keyword {\
color: var(--cerb-editor-syntax-var);\
}\
.ace-cerb .ace_variable {\
color: var(--cerb-editor-syntax-var);\
}\
.ace-cerb .ace_cerb-uri {\
pointer-events: all;\
}\
.ace-cerb .ace_cerb-uri:hover {\
text-decoration: underline;\
cursor: pointer;\
}\
.ace-cerb .ace_operator {\
color: var(--cerb-editor-syntax-oper);\
font-weight: normal;\
}\
.ace-cerb .ace_constant.ace_buildin {\
color: rgb(88, 72, 246);\
}\
.ace-cerb .ace_constant.ace_library {\
color: rgb(6, 150, 14);\
}\
.ace-cerb .ace_function {\
color: var(--cerb-editor-syntax-function);\
}\
.ace-cerb .ace_string {\
color: var(--cerb-editor-syntax-string);\
}\
.ace-cerb .ace_comment {\
color: var(--cerb-editor-syntax-comment);\
}\
.ace-cerb .ace_comment.ace_doc {\
color: rgb(63, 95, 191);\
}\
.ace-cerb .ace_comment.ace_doc.ace_tag {\
color: rgb(127, 159, 191);\
}\
.ace-cerb .ace_constant.ace_numeric {\
color: var(--cerb-editor-syntax-numeric);\
}\
.ace-cerb .ace_tag {\
color: var(--cerb-editor-syntax-tag);\
}\
.ace-cerb .ace_type {\
color: var(--cerb-editor-syntax-type);\
}\
.ace-cerb .ace_xml-pe {\
color: rgb(104, 104, 91);\
}\
.ace-cerb .ace_marker-layer .ace_selection {\
background: var(--cerb-editor-selection);\
}\
.ace-cerb .ace_marker-layer .ace_bracket {\
margin: -1px 0 0 -1px;\
border: 1px solid var(--cerb-editor-syntax-bracket);\
}\
.ace-cerb .ace_meta.ace_tag {\
color: var(--cerb-editor-syntax-tag);\
}\
.ace-cerb .ace_meta.ace_tag.ace_twig {\
color: var(--cerb-editor-syntax-twig);\
}\
.ace-cerb .ace_invisible {\
color: var(--cerb-color-background-contrast-220);\
}\
.ace-cerb .ace_entity.ace_other.ace_attribute-name {\
color: rgb(25, 118, 116);\
}\
.ace-cerb .ace_marker-layer .ace_step {\
background: var(--cerb-editor-marker);\
}\
.ace-cerb .ace_active-line {\
background: var(--cerb-editor-line-active);\
}\
.ace-cerb .ace_gutter-active-line {\
background-color: var(--cerb-color-background-contrast-220);\
}\
.ace-cerb .ace_marker-layer .ace_selected-word {\
border: 1px solid rgb(181, 213, 255);\
}\
.ace-cerb .ace_snippet-marker {\
border: 0;\
}\
.dark .ace-cerb .ace_indent-guide {\
background: url(\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAACCAYAAACZgbYnAAAACXBIWXMAAAsSAAALEgHS3X78AAAAEklEQVQImWOwtLT8z8TAwMAAAAtmAa30QJ4OAAAAAElFTkSuQmCC\") right repeat-y;\
}\
.ace-cerb .ace_indent-guide {\
background: url(\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAACCAYAAACZgbYnAAAACXBIWXMAAAsSAAALEgHS3X78AAAAEklEQVQImWN49uzZfyYGBgYGABueA7QT9sNDAAAAAElFTkSuQmCC\") right repeat-y;\
}";

exports.cssClass = "ace-cerb";

var dom = require("../lib/dom");
dom.importCssString(exports.cssText, exports.cssClass);
});
