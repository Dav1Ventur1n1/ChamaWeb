FROM nginx:alpine
RUN apk add --no-cache openssl \
 && mkdir /etc/nginx/ssl \
 && openssl req -x509 -nodes -days 365 \
    -subj "/CN=localhost" \
    -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/self.key \
    -out /etc/nginx/ssl/self.crt
COPY default.conf /etc/nginx/conf.d/default.conf
