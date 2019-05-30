define("ace/theme/cerb",["require","exports","module","ace/lib/dom"], function(require, exports, module) {
"use strict";

exports.isDark = false;
exports.cssText = ".ace-cerb .ace_gutter {\
background: #ebebeb;\
border-right: 1px solid rgb(159, 159, 159);\
color: rgb(136, 136, 136);\
}\
.ace-cerb .ace_print-margin {\
width: 1px;\
background: #ebebeb;\
}\
.ace-cerb {\
background-color: #FFFFFF;\
color: black;\
}\
.ace-cerb .ace_fold {\
background-color: rgb(60, 76, 114);\
}\
.ace-cerb .ace_cursor {\
color: black;\
}\
.ace-cerb .ace_storage,\
.ace-cerb .ace_keyword {\
color: rgb(127, 0, 85);\
}\
.ace-cerb .ace_variable {\
color: rgb(127, 0, 85);\
}\
.ace-cerb .ace_operator {\
color: rgb(50, 50, 50);\
font-weight: normal;\
}\
.ace-cerb .ace_constant.ace_buildin {\
color: rgb(88, 72, 246);\
}\
.ace-cerb .ace_constant.ace_library {\
color: rgb(6, 150, 14);\
}\
.ace-cerb .ace_function {\
color: rgb(60, 76, 114);\
}\
.ace-cerb .ace_string {\
color: rgb(42, 0, 255);\
}\
.ace-cerb .ace_comment {\
color: rgb(113, 150, 130);\
}\
.ace-cerb .ace_comment.ace_doc {\
color: rgb(63, 95, 191);\
}\
.ace-cerb .ace_comment.ace_doc.ace_tag {\
color: rgb(127, 159, 191);\
}\
.ace-cerb .ace_constant.ace_numeric {\
color: darkblue;\
}\
.ace-cerb .ace_tag {\
color: rgb(25, 118, 116);\
}\
.ace-cerb .ace_type {\
color: rgb(127, 0, 127);\
}\
.ace-cerb .ace_xml-pe {\
color: rgb(104, 104, 91);\
}\
.ace-cerb .ace_marker-layer .ace_selection {\
background: rgb(181, 213, 255);\
}\
.ace-cerb .ace_marker-layer .ace_bracket {\
margin: -1px 0 0 -1px;\
border: 1px solid rgb(192, 192, 192);\
}\
.ace-cerb .ace_meta.ace_tag {\
color:rgb(25, 118, 116);\
}\
.ace-cerb .ace_meta.ace_tag.ace_twig {\
color: rgb(127, 0, 85);\
}\
.ace-cerb .ace_invisible {\
color: #ddd;\
}\
.ace-cerb .ace_entity.ace_other.ace_attribute-name {\
color: rgb(25, 118, 116);\
}\
.ace-cerb .ace_marker-layer .ace_step {\
background: rgb(255, 255, 0);\
}\
.ace-cerb .ace_active-line {\
background: rgb(232, 242, 254);\
}\
.ace-cerb .ace_gutter-active-line {\
background-color : #DADADA;\
}\
.ace-cerb .ace_marker-layer .ace_selected-word {\
border: 1px solid rgb(181, 213, 255);\
}\
.ace-cerb .ace_indent-guide {\
background: url(\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAACCAYAAACZgbYnAAAAE0lEQVQImWP4////f4bLly//BwAmVgd1/w11/gAAAABJRU5ErkJggg==\") right repeat-y;\
}";

exports.cssClass = "ace-cerb";

var dom = require("../lib/dom");
dom.importCssString(exports.cssText, exports.cssClass);
});
