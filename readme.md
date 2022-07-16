# Auto category for posts

This repository is the base for the plugin _Auto category for posts_. This provides the possibility to set a category on new posts before they are saved. It also allows to set the default category in the category-list.

## Usage

Nothing to do after checkout this repository.

## Release

1. increase the version number in _build/build.properties_.
2. execute the following command in _build/_: `ant build`
3. after that you will finde in the release directory a zip file which could be used in WordPress to install it.

## Translations

I recommend to use [PoEdit](https://poedit.net/) to translate texts for this plugin.

### generate pot-file

Run in main directory:

`wp i18n make-pot . languages/auto-category-for-posts.pot`

### update translation-file

1. Open .po-file of the language in PoEdit.
2. Go to "Translate > "Update from POT-file".
3. After this the new entries are added to the language-file.

### export translation-file

1. Open .po-file of the language in PoEdit.
2. Go to File > Save.
3. Upload the generated .mo-file and the .po-file to the plugin-folder languages/

### generate json-translation-files

Run in main directory:

`wp i18n make-json languages`

OR use ant in build/-directory: `ant json-translations`