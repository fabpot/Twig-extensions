The Addslashes Extension
========================

The Addslashes extensions is very simple and provides the following filter:

* ``addslashes``

The addslashes_ filter is just a wrapper to the php function with the same name.
Useful for ie: javascript var in twig templates.

How to use it
-------------

in your Acme/YourBundle/Resources/config/services.yml::

	services:
		twig.extension.txt:
			class: Twig_Extensions_Extension_Addslashes
			tags:
				- { name: twig.extension }

.. _`addslashes`:       http://www.php.net/addslashes