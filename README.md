## Ievads

Žurnāla "Ir" izdevēji nepiedāvā saviem abonentiem vienkāršu veidu, kā to automātiski saņemt un lasīt savā elektroniskajā lasītājā (iPad, Kindle, u.c.) uzreiz, kad ir izdots jaunākais tā numurs.

Šis rīks automātiski atrod jaunākā izdevuma numuru ir.lv, pievienojas ir.lv ar tavu lietotāja vārdu un paroli, un lejuplādē izdevuma lappuses JPG formātā, saglabā tās vienā PDF failā un nosūta uz norādīto epasta adresi.

Šis rīks ir paredzēts **TIKAI UN VIENĪGI** personīgai lietošanai.


## Atkarības

*  PHP ar [`file_get_contents`](http://php.net/manual/en/function.file-get-contents.php) un [`fopen wrappers`](http://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen) atbalstu.
*  [ImageMagick](http://www.imagemagick.org/) pakotne ar komandrindas atbalstu.

## Kā lietot?

1.  Atver `ir.php` un norādi savu ir.lv lietotāja vārdu un paroli, kā arī epastus, uz kuriem nosūtīt izveidoto PDF.
2.  Darbini rīku no komandrindas:

		php ir.php

    vai uzstādi Cronjob katras dienas rītā:

		0 4 * * * php /home/username/ir/ir.php