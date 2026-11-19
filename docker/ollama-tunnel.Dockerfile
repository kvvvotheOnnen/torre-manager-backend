FROM alpine:3.20

RUN apk add --no-cache openssh-client

COPY docker/ollama-tunnel-entrypoint.sh /usr/local/bin/ollama-tunnel-entrypoint
RUN chmod +x /usr/local/bin/ollama-tunnel-entrypoint

ENTRYPOINT ["/usr/local/bin/ollama-tunnel-entrypoint"]
CMD ["ssh", "-N", "-i", "/ssh-key", "-o", "IdentitiesOnly=yes", "-o", "StrictHostKeyChecking=yes", "-o", "ExitOnForwardFailure=yes", "-o", "ServerAliveInterval=15", "-o", "ServerAliveCountMax=3", "-o", "TCPKeepAlive=yes", "-L", "0.0.0.0:11434:127.0.0.1:11434", "app-server"]
