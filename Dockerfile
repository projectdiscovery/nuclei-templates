FROM python:3.11-alpine
RUN pip install flask
WORKDIR /app
COPY mock_fortiweb.py .
EXPOSE 8080
CMD ["python", "mock_fortiweb.py"]
