FROM node:latest

WORKDIR /var/www/

# nodemon is a development utility that watches source files and reloads server on change.
# This allows us to use a bind mount rather than copying at build time, which makes reloads faster
RUN npm install nodemon --global

CMD ["nodemon", "src/mock_wordpress_server.js"]