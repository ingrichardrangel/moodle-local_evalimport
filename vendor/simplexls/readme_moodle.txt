SimpleXLS, upstream master retrieved 2026-09-22.
Source: https://github.com/shuchkin/simplexls/blob/master/src/SimpleXLS.php
Licence: MIT (see LICENSE).
Bundled because Moodle deliberately removes PhpSpreadsheet XLS/OLE support.
Local changes: isolate namespace; fail on invalid parser initialization; bound
parser iteration work; reject cyclic/negative OLE sector chains; bound FAT/DIFAT
counts; preserve raw zero; reset each MULRK cell's numeric/date type.
Plugin-specific validation is in classes/local/xls_reader.php.
