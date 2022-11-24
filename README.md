# Zefania Bibles

This is a collection of all Zefania XML Bible translations from the [Zefania XML Bible Markup Language](https://sourceforge.net/projects/zefania-sharp/) project for the use in the [Order of Mass](https://github.com/tommander/catholic-mass) project.

## Original project

[Zefania XML Bible Markup Language](https://sourceforge.net/projects/zefania-sharp/) is a great GNU GPL licensed project that introduces a simple and easy-to-understand XML structure for Bible translations and at the same time contains a lot of translations in different languages.

A tool is also included that can present the XML files in a nice way to read Bible (even in other XML-based formats).

You can read more about that project also on [German Wikipedia](https://de.wikipedia.org/wiki/Zefania_XML) or [The Digital Classicist Wiki](https://wiki.digitalclassicist.org/Zefania_XML_Bible_Markup).

All XML files from that project are included in the `_xml.7z` file, so you don't have to download them manually. These files are available under the license as described in each file or under the same license as the project.

That shouldn't stop you from checking the project, contributing to it or supporting their great job!

## This project

So why does **this** project exist? It is just because we wanted to include these translations in the [Order of Mass](https://github.com/tommander/catholic-mass) web application, so that daily readings can be included in the text of the mass and that the whole Bible can be easily read on the web.

However, the original project does not exist on a live CVS (svn, git, ...) and all XML files are available on SourceForge only as compressed ZIP files.

As we want our PHP/JS scripts to read these files automatically, we came up with this repository.

One change is that all files are supposed to be transformed to JSON, which is a bit easier and faster to read by the web app, when it is looking for specific verses.

## How to start

- `git clone` this repo
- Make sure subfolders `json`, `map`, `meta` and `xml` exist in the root of the working copy
- Extract all files from the `_xml.7z` archive to the `xml` subfolder
- Run `Zefania.php`

If you already have a working copy of the [Order of Mass](https://github.com/tommander/catholic-mass) repo, this repo comes as a submodule in `libs/zefania-bibles`, so instead of the `git clone`, make sure you updated submodules.

And no worries, all generated files (incl. the extracted XML files) are *gitignored*.

Note: a PHP warning about `booklist.json` may appear when running `Zefania.php` - it just means you do not have this repo under the [Order of Mass](https://github.com/tommander/catholic-mass) repo and as a result, book numbers will not be correctly mapped to book names in those translations, where book names are not included in the XML. Fix - move the repo or download the missing JSON file and change the path in `Zefania.php`, line 471.

## Repo size

Now this is what I want to warn you about - once the XML files are extracted and the resulting JSON files are created, the whole repo will take around 2.53 GiB (out of that 1.17 GiB for XML files and 1.19 GiB for JSON files). So just be prepared for that.

## Help

If you need any help or have a question regarding this project, feel free to contact me via [e-mail](mailto:tommander@tommander.cz) or via other communication options available here on GitHub (e.g. [Issues](https://github.com/tommander/zefania-bibles/issues)). I'll be happy to help you :handshake:

## Maintainers and contributors

- :man_office_worker: [@tommander](https://github.com/tommander) (active maintainer)
