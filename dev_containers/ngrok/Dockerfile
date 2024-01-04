FROM ubuntu:latest

RUN apt-get update && \
    apt-get install -y \
    curl \
    coreutils

# Install ngrok
RUN curl -s https://ngrok-agent.s3.amazonaws.com/ngrok.asc | tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null && echo 'deb https://ngrok-agent.s3.amazonaws.com buster main' | tee /etc/apt/sources.list.d/ngrok.list && apt update && apt install ngrok

COPY ./ngrok/start_ngrok.bash .

CMD ["/bin/bash", "-c", "./start_ngrok.bash"]
