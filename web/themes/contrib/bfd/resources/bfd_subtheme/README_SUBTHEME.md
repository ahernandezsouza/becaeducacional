INSTALLATION OF THE SUBTHEME
---------------

Copy the `bfd_subtheme` folder to `/sites/themes/custom` folder and set it as
default theme.

If you are using a multisites Drupal instance you have to copy the
"Bootstrap for drupal" base theme to your site specific folder `/themes/contrib`
or change the relative path in the SCSS master file -
`bfd_subtheme/assets/scss/style.scss`.

SUBTHEME USAGE
------

# CSS
To use a custom CSS go to the theme settings in
`/admin/appearance/settings/bootstrap_for_drupal_subtheme` Tick "Use bfd-custom.css".
That setting will create a "bfd-custom.css" in `files` folder that you can use
to customize the theme.

# SCSS
You can theme directly with the file `bfd_subtheme/assets/scss/base/_base.scss`
and add SCSS files to the master SCSS file
`bfd_subtheme/assets/scss/tools/_subtheme.scss`. Gulp file is provided to
compile SCSS but you will have to install node.js modules.

ALTERNATIVE WITHOUT SUBTHEME
--------------

# CSS
To use a custom CSS go to the theme settings in
`/admin/appearance/settings/bootstrap_for_drupal` Tick "Use bfd-custom.css".
That setting will create a "bfd-custom.css" in `files` folder that you can use
to customize the theme.

# SCSS
In the theme, there is a SCSS template folder for you to add your custom CSS
without subtheme. Find in `assets/scss/tools` a `custom` folder, copy it to
`assets/scss/` and in the bottom of the file `assets/scss/style.scss`
uncomment the line `@import 'custom/include`. **After updating the theme**
dont forget to uncomment that line again. Gulp file is provided to
compile SCSS but you will have to install node.js modules.
