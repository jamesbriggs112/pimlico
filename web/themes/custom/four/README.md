# README #

Very basic Drupal theme foundation for Four projects.

### What is this repository for? ###

This theme's designed not to be used as a parent theme but to be copied / forked and used as a foundation to build on.  Is is deliberately sparse and does _not_ impose any particular layout methodology or design.

It has the following features:

* Laravel Mix sass workflow
* Normalisation both for standard elements and a few tricky Drupal elements
* Structured sass folder to help you get set up as quickly as you can
* Template overrides for the main page areas
* Stubs for some important functions already in place
* Hamburger menu in place for mobile

### How do I get set up? ###

It's pretty straightforward.

TBC

### Using the  Laravel Mix workflow to compile your CSS ###

If you're going to use the Gulp workflow then you need to:

* Run `yarn watch` when you'd like to compile your CSS files

### Who do I talk to? ###

* If you create anything useful (e.g. bits of javascript, sass mixins) then please do submit a pull request

## Styling

### Breakpoints

https://github.com/sass-mq/sass-mq

Set your breakpoints in `sass/init/_mq-settings.scss`

##### Usage

```
@use '~sass-mq/mq' as *;

.root {
  @include mq($from: tablet) {
    // Breakpoint styles
  }
}
```

### sass-rem

Where possible you should be using rem rather than px which can be easily using the sass-rem mixin

https://github.com/pierreburel/sass-rem

##### Usage

```
@use '~sass-rem' as to;

.root {
  font-size: to.rem(18px);
}
```

### CSS Variables

Where possible use css variables, some base variable have been include already here `sass/init/_root.scss`

There are exceptions to this for example if you have a mixin that requires a varible, these should be set in `styles/base/_variables.scss` and these can then be passed to a css variable if needed.

For example the min and max page width are set here

```
$default-min-bp: 320px;
$default-max-bp: 1245px;
```

### Mixins

Mixins can be found in `sass/init/_mixins.scss` and includes the following helpers

#### fluid

The fluid allows to scale fonts and other pixel based values such as margins and padding based of the screen width

##### Usage

```
@use '../init/mixins' as *;

.root {
  font-size: fluid(18px, 60px);
}
```