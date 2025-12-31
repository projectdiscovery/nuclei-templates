FROM python:3.9-slim
WORKDIR /app
RUN pip install flask requests
COPY server.py .
EXPOSE 8001
CMD ["python", "-u", "server.py"]
