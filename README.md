# Secure PDF
----------
- Secure PDF module allow you to upload a PDF to your course, and prevent students from downloading it.
- Students will get an image of each page and not the PDF itself.
- The images are protected from "right click" to prevent saving the image.
- Module completion will be set only while user saw all pages of document.
- You must know that people with web development skills will be able to download the images (one by one)

# Install
---------
## Please note that you have to install a PHP module that is not needed by Moodle itself.
- Install php-imagick module on your system.
- (debian/ubuntu) apt-get install php-imagick
- (Redhat/Centos) yum install php-imagick
-  Configure imagemagick to allow PDF reading, Add &lt;policy domain="coder" rights="read" pattern="PDF"&gt;  to the policy at /etc/ImageMagick-6/policy.xml see more details here : https://stackoverflow.com/questions/52703123/override-default-imagemagick-policy-xml
- Restart php-fpm or your web server.
- cd [moodle]/mod/
- git clone https://github.com/yedidiaklein/moodle-mod_securepdf.git securepdf
- Go to your moodle Notification Page and install. 

# Development
---------
  If you want to change the Vue components or the javascript code, you have to install all dependencies:


  `cd vue`
  `yarn install`
  
  With `yarn serve` you can build the file `amd/build/app-lazy.min.js` (in development mode) that will be loaded by the browser. 
  Watch does not exit, it will re-build the file whenever one of the source files was touched.

  Important: Before a commit you should build the file with `yarn build` (production mode). This generates a much smaller file.       However, the file is not suitable for debugging using the browser web developer tools.

  Hint: you should disable Moodle's javascript cache. You may add the line `$CFG->cachejs = false;` to `config.php`. If you disable caching, Moodle takes the file from the `amd/src` folder. Hence, there is a symbolic link to `../build/app-lazy.min.js`.

  If you want to use javascript libraries from Moodle, you have to declare them as external dependencies in `vue/webpack.config.js` under exports.externals.

# Use
-----
- Add securepdf module in your course.
- Add a PDF fle to the module and watch it.
- Note that first view of page will be slow (20-25 seconds), then it's will cached for other users.
- Enjoy! 

# License
---
- See the LICENSE file for licensing details.

# About
-----
- Secure PDF module was written by Yedidia Klein from OpenApp Israel.

# TODO
----
- Support upload of MS-PowerPoint files. (Please contact me about that..)
