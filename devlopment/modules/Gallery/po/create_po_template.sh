#!/bin/sh
# $Id: create_po_template.sh,v 1.9 2003/10/17 13:32:29 jefmcg Exp $
#
#note: requires xgettext version 0.12.1 or greater
#
#Note: for version 1.4.2, to support email internationalisation, need to 
#add keyword i18n to xgettext call


##### CORE .pot ############
echo '# $Id: create_po_template.sh,v 1.9 2003/10/17 13:32:29 jefmcg Exp $' > gallery-core.pot
cat copyright.txt >> gallery-core.pot

xgettext --files-from=filelist-core -LPHP --keyword=_ --no-wrap --msgid-bugs-address="gallery-translations@lists.sourceforge.net" -o - | tail +7 >> gallery-core.pot

##### CONFIG .pot
echo '# $Id: create_po_template.sh,v 1.9 2003/10/17 13:32:29 jefmcg Exp $' > gallery-config.pot
cat copyright.txt >> gallery-config.pot

xgettext --files-from=filelist-config -LPHP --keyword=_ --no-wrap --msgid-bugs-address="gallery-translations@lists.sourceforge.net" -o - | tail +7 >> gallery-config.pot
