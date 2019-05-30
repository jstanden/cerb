define("ace/mode/cerb_query_highlight_rules",["require","exports","module","ace/lib/oop","ace/mode/text_highlight_rules","ace/mode/cerb_query_twig_hightlight_rules"], function(require, exports, module) {
"use strict";

var oop = require("../lib/oop");
var TextHighlightRules = require("./text_highlight_rules").TextHighlightRules;
var CerbQueryTwigHighlightRules = require("./cerb_query_twig_highlight_rules").CerbQueryTwigHighlightRules;

var CerbQueryHighlightRules = function() {
    // regexp must not have capturing parentheses. Use (?:) instead.
    // regexps are ordered -> the first match is used
    var newRules = {
        "start" : [
            {
                token: ["whitespace","meta.tag","whitespace","keyword.operator","paren.lparen"],
                regex: "(\\s*)([a-zA-z\\.\\_]*?\\:)(\\s*)(\\!)(\\()"
            }, {
                token: ["whitespace","meta.tag","whitespace","paren.lparen"],
                regex: "(\\s*)([a-zA-z\\.\\_]*?\\:)(\\s*)(\\()"
            }, {
                token: ["whitespace","meta.tag","whitespace"],
                regex: /(\s*)([a-zA-z0-9\.\_]*?\:)(\s*)/
            }, {
                token : "string", // single line
                regex : '["](?:(?:\\\\.)|(?:[^"\\\\]))*?["]'
            }, {
                token : "string", // single quoted string
                regex : "['](?:(?:\\\\.)|(?:[^'\\\\]))*?[']"
            }, {
                token : "constant.numeric", // float
                regex : /(\b|[+\-\.])[\d_]+(?:(?:\.[\d_]*)?(?:[eE][+\-]?[\d_]+)?)(?=[^\d-\w]|$)/
            }, {
                token : "constant.numeric", // other number
                regex : /[+\-]?\.inf\b|NaN\b|0x[\dA-Fa-f_]+|0b[10_]+/
            }, {
                token : "keyword.operator",
                regex : "\\b(?:AND|OR)\\b"
            }, {
                token : "constant.language.boolean",
                regex : "\\b(?:true|false|TRUE|FALSE|True|False|yes|no)\\b"
            }, {
                token : "variable.other.readwrite.local.twig",
                regex : "\\{\\{-?",
                next  : "twig-start"
            }, {
                token : "meta.tag.twig",
                regex : "\\{%-?",
                next  : "twig-start"
            }, {
                token : "variable.other.readwrite.local.twig",
                regex : "\{\{",
                next  : "twig-start"
            }, {
                token : "comment.block.twig",
                regex : "\\{#-?",
                next  : "twig-comment"
            }, {
                token : "paren.lparen",
                regex : "[[(]",
                merge : false
            }, {
                token : "paren.rparen",
                regex : "[\\])]",
                merge : false
            }, {
                token : "whitespace",
                regex : "\\s+"
            }, {
                token : "text",
                regex : /[^\s,:\[\]\{\}\(\)]+/
            }
        ]
    };
    
    this.$rules = newRules;
    
    var twigRules = new CerbQueryTwigHighlightRules().getRules();

    this.addRules(twigRules);
    
    this.normalizeRules();
};

oop.inherits(CerbQueryHighlightRules, TextHighlightRules);

exports.CerbQueryHighlightRules = CerbQueryHighlightRules;
});

define("ace/mode/cerb_query_twig_highlight_rules",["require","exports","module","ace/lib/oop","ace/mode/text_highlight_rules"], function(require, exports, module) {
var oop = require("../lib/oop");
var lang = require("../lib/lang");
var TextHighlightRules = require("./text_highlight_rules").TextHighlightRules;

var CerbQueryTwigHighlightRules = function() {
    var tags = "autoescape|block|do|embed|extends|filter|flush|for|from|if|import|include|macro|sandbox|set|spaceless|use|verbatim";
    tags = tags + "|end" + tags.replace(/\|/g, "|end");
    var filters = "abs|batch|capitalize|convert_encoding|date|date_modify|default|e|escape|first|format|join|json_encode|keys|last|length|lower|merge|nl2br|number_format|raw|replace|reverse|slice|sort|split|striptags|title|trim|upper|url_encode";
    var functions = "attribute|constant|cycle|date|dump|parent|random|range|template_from_string";
    var tests = "constant|divisibleby|sameas|defined|empty|even|iterable|odd";
    var constants = "null|none|true|false";
    var operators = "b-and|b-xor|b-or|in|is|and|or|not";

    var keywordMapper = this.createKeywordMapper({
        "keyword.control.twig": tags,
        "support.function.twig": [filters, functions, tests].join("|"),
        "keyword.operator.twig":  operators,
        "constant.language.twig": constants
    }, "identifier");

    this.$rules = {
        "twig-start": [
            {
                token : "variable.other.readwrite.local.twig",
                regex : "-?\\}\\}",
                next : "pop"
            }, {
                token : "meta.tag.twig",
                regex : "-?%\\}",
                next : "pop"
            }, {
                token : "string",
                regex : "'",
                next  : "twig-qstring"
            }, {
                token : "string",
                regex : '"',
                next  : "twig-qqstring"
            }, {
                token : "constant.numeric", // hex
                regex : "0[xX][0-9a-fA-F]+\\b"
            }, {
                token : "constant.numeric", // float
                regex : "[+-]?\\d+(?:(?:\\.\\d*)?(?:[eE][+-]?\\d+)?)?\\b"
            }, {
                token : "constant.language.boolean",
                regex : "(?:true|false)\\b"
            }, {
                token : keywordMapper,
                regex : "[a-zA-Z_$][a-zA-Z0-9_$]*\\b"
            }, {
                token : "keyword.operator.assignment",
                regex : "=|~"
            }, {
                token : "keyword.operator.comparison",
                regex : "==|!=|<|>|>=|<=|==="
            }, {
                token : "keyword.operator.arithmetic",
                regex : "\\+|-|/|%|//|\\*|\\*\\*"
            }, {
                token : "keyword.operator.other",
                regex : "\\.\\.|\\|"
            }, {
                token : "punctuation.operator",
                regex : /\?|:|,|;|\./
            }, {
                token : "paren.lparen",
                regex : /[\[\({]/
            }, {
                token : "paren.rparen",
                regex : /[\])}]/
            }, {
                token : "text",
                regex : "\\s+"
            }
        ],
        "twig-qqstring": [
            {
                token : "constant.language.escape",
                regex : /\\[\\"$#ntr]|#{[^"}]*}/
            }, {
                token : "string",
                regex : '"',
                next  : "twig-start"
            }, {
                defaultToken : "string"
            }
        ],
        "twig-qstring": [
            {
                token : "constant.language.escape",
                regex : /\\[\\'ntr]}/
            }, {
                token : "string",
                regex : "'",
                next  : "twig-start"
            }, {
                defaultToken : "string"
            }
        ],
        "twig-comment": [
            {
                token : "comment.block.twig",
                regex : ".*-?#\\}",
                next : "pop"
            }
        ]
    };
    
    this.normalizeRules();
};

oop.inherits(CerbQueryTwigHighlightRules, TextHighlightRules);

exports.CerbQueryTwigHighlightRules = CerbQueryTwigHighlightRules;
});

define("ace/mode/matching_brace_outdent",["require","exports","module","ace/range"], function(require, exports, module) {
"use strict";

var Range = require("../range").Range;

var MatchingBraceOutdent = function() {};

(function() {

    this.checkOutdent = function(line, input) {
        if (! /^\s+$/.test(line))
            return false;

        return /^\s*\}/.test(input);
    };

    this.autoOutdent = function(doc, row) {
        var line = doc.getLine(row);
        var match = line.match(/^(\s*\})/);

        if (!match) return 0;

        var column = match[1].length;
        var openBracePos = doc.findMatchingBracket({row: row, column: column});

        if (!openBracePos || openBracePos.row == row) return 0;

        var indent = this.$getIndent(doc.getLine(openBracePos.row));
        doc.replace(new Range(row, 0, row, column-1), indent);
    };

    this.$getIndent = function(line) {
        return line.match(/^\s*/)[0];
    };

}).call(MatchingBraceOutdent.prototype);

exports.MatchingBraceOutdent = MatchingBraceOutdent;
});

define("ace/mode/cerb_query",["require","exports","module","ace/lib/oop","ace/mode/text","ace/mode/cerb_query_highlight_rules","ace/mode/matching_brace_outdent"], function(require, exports, module) {
"use strict";

var oop = require("../lib/oop");
var TextMode = require("./text").Mode;
var CerbQueryHighlightRules = require("./cerb_query_highlight_rules").CerbQueryHighlightRules;
var MatchingBraceOutdent = require("./matching_brace_outdent").MatchingBraceOutdent;

var Mode = function() {
    this.HighlightRules = CerbQueryHighlightRules;
    this.$outdent = new MatchingBraceOutdent();
    this.$behaviour = this.$defaultBehaviour;
};
oop.inherits(Mode, TextMode);

(function() {

    this.lineCommentStart = "{#";
    
    this.getNextLineIndent = function(state, line, tab) {
        var indent = this.$getIndent(line);

        if (state == "start") {
            var match = line.match(/^.*[\{\(\[]\s*$/);
            if (match) {
                indent += tab;
            }
        }

        return indent;
    };

    this.checkOutdent = function(state, line, input) {
        return this.$outdent.checkOutdent(line, input);
    };

    this.autoOutdent = function(state, doc, row) {
        this.$outdent.autoOutdent(doc, row);
    };


    this.$id = "ace/mode/cerb_query";
}).call(Mode.prototype);

exports.Mode = Mode;

});