id: dockerfile-hidden-disclosure

info:
  name: Dockerfile Hidden Disclosure
  author: dhiyaneshDk
  severity: medium
  reference: https://github.com/detectify/ugly-duckling/blob/master/modules/crowdsourced/dockerfile-hidden-disclosure.json
  tags: exposure,config

requests:
  - method: GET
    path:
      - "{{BaseURL}}/.dockerfile"
      - "{{BaseURL}}/.Dockerfile"

    matchers-condition: and
    matchers:
      - type: regex
        regex:
          - '^(?:FROM(?:CACHE)?|RUN|ADD|WORKDIR|ENV|EXPOSE|\#)\s+[ -~]+'
        part: body

      - type: status
        status:
          - 200

      - type: word
        part: header
        words:
          - "text/html"
        negative: true