# README #

Very basic Drupal theme foundation for Marmelo projects.

*Important: both the Drupal 7 and Drupal 8/9 branches are in this repository.  Check out the 7.x or 8.x branches for the latest versions.*

### What is this repository for? ###

This theme's designed not to be used as a parent theme but to be copied / forked and used as a foundation to build on.  Is is deliberately sparse and does _not_ impose any particular layout methodology or design.  

It has the following features:

* Gulp sass workflow
* Normalisation both for standard elements and a few tricky Drupal elements
* Structured sass folder to help you get set up as quickly as you can
* Template overrides for the main page areas 
* Stubs for some important functions already in place 
* Hamburger menu in place for mobile

### How do I get set up? ###

It's pretty straightforward.  

1. Navigate to your project's `themes` folder
2. Run `mkdir custom && cd custom`
3. Clone the relevant branch of the repo here, then delete the .git folder
4. Pick a name for the theme then rename the theme folder accordingly
5. Run `npm install`
6. Run `gulp theme-init` and follow the instructions

### Gulp theme-init will do the following automagically for you ###

* Rename the .info.yml file
* Rename the .libraries.yml file
* Rename the marmelo.theme file to [themename].theme
* Rename the functions in the .theme file
* Amend the info in the .info file 
* Amend the info in the .libraries.yml file
* Completely change the theme in every way to suit your actual needs

### Using the Gulp workflow to compile your CSS ###

If you're going to use the Gulp workflow then you need to:

* Run `gulp` when you'd like to compile your CSS files
* Or `gulp watch` to automatically watch & compile your CSS files

### Who do I talk to? ###

* This is Luke's code so talk to him
* If you create anything useful (e.g. bits of javascript, sass mixins) then please do submit a pull request
