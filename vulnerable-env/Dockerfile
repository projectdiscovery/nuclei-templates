FROM python:3.9-slim
WORKDIR /app
RUN pip install flask requests
COPY mock_exchange.py .
EXPOSE 8000
CMD ["python", "-u", "mock_exchange.py"]
