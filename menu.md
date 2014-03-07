Configuration of menu entries. The menu entries can be configured via the
[`Configuration` class](http://book.cakephp.org/2.0/en/development/configuration.html) 
of CakePHP.

With the menu configuration you can add menu items from your plugin within you
plugins `bootstrap.php` file.

# Short format

`menu.[menu-name].[title] = [url]`

Example:

    Configure::write(array('menu.backend.MyPlugin' => '/myPlugin/myController'));

# Options

`menu.[menu-name].[title].[option]`

Following options are supported

* `id`: Id of the menu item. Default given `title`
* `parent`: `String` id of parent menu
* `title`: `String` of item title
* `url`: Url as `String` or `Array`.
* `plugin`: `String` of plugin.
* `controler`: `String` of controller.
* `action`: `String` of url action.
* `active`: `Boolean` if the item is active.
* `disabled`: `Boolean` if the item is disabled. This item will be shown.
* `deactivated`: `Boolean` if the itmem should not be shown
* `priority`: Priority for sort order. Default is `10`.
* `requiredRole`: Required Role. Possible roles are `ROLE_NOBODY`, `ROLE_GUEST`,
`ROLE_USER`, `ROLE_SYSOP`, `ROLE_ADMIN`. Default is `ROLE_NOBODY`
* `roles`: `Array` of user role. Possible roles are `ROLE_NOBODY`, `ROLE_GUEST`,
`ROLE_USER`, `ROLE_SYSOP`, `ROLE_ADMIN`. Default is `ROLE_NOBODY`

Example:

    $options = array(
      'title' => 'My Plugin Title',
      'url' => array('plugin' => 'MyPlugin', 'controller' => 'MyPlugin'),
      'priority' => 9
    );
    $name = 'menu.backend.MyPlugin';
    Configure::write($name, Hash::merge($options, (array) Configure::read($name)));

# Menus

There are several menus within phTagr

* `backend`: The main menu for the backend
* `nav-top`: Top navigation menu for the gallery
* `nav-main`: Gallery's main navigation menu
* `nav-explorer`: Explorer's menu