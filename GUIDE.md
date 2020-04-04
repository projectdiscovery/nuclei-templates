## Templating Guide

**Nuclei** is based on the concepts of `YAML` based template files that define how the requests will be sent and processed. This allows easy extensibility capabilities to nuclei.

The templates are written in `YAML` which specifies a simple human readable format to quickly define the execution process. 

Let's start with the basics and define our own workflow file for detecting the presence of a `.git/config` file on a webserver and take it from there.



### Defining a .git/config detection template

Each template has a unique ID which is used during output writing to specify the template name for an output line.

The template file ends with **.yaml** extension. Start by creating a file named `git-config.yaml` in your favorite text editor and defining an **<u>id</u>** for our sample template.

```yaml
# id contains the unique identifier for the template.
id: git-config
```

We name our template file as `git-config`. You can go ahead and name it whatever you desire. 


Next important piece of information about a template is the <u>**info**</u> block. Info block provides more context on the purpose of the template and the **author**. It can also contain a **severity** field which indicates the severity of the template.

Let's add an **info** block to our template as well.

```yaml
info:
  # name is the name of the template
  name: Git Config File Detection Template
  # author is the name of the author for the template
  author: Ice3man
  # severity is the severity for the template.
  severity: medium
```

Actual requests and corresponding matchers are placed below the info block and they perform the task of making requests to target servers and finding if the template request was succesful.

Each template file can contain multiple requests to be made. The template is iterated and one by one the desired HTTP requests are made to the target sites.

Requests start with a request block which specifies the start of the requests for the template. 

```yaml
# start the requests for the template right here
requests:
```

Requests can be fine tuned to perform the exact tasks as desired. Nuclei requests are fully configurable meaning you can configure and define each and every single thing about the requests that will be sent to the target servers.

1. First thing in the request is <u>**method**</u>. Request method can be **GET**, **POST**, **PUT**, **DELETE**, etc depending on the needs. 

   ```yaml
   # method is the method for the request
   method: GET
   ```

2. The next part of the requests is the **path** of the request path. Dynamic variables can be placed in the path to modify its behaviour on runtime. Variables start with `{{` and end with `}}` and are case-sensitive. 

   1. **BaseURL** - Placing BaseURL as a variable in the path will lead to it being replaced on runtime in the request by the original URL as specified in the target file.
   2. **Hostname** - Hostname variable is replaced by the hostname of the target on runtime.

   Some sample dynamic variable replacement examples - 

   ```yaml
   path: {{BaseURL}}/.git/config
   # this path will be replaced on execution with BaseURL
   # When BaseURL = https://abc.com
   # path will get replaced to the following - 
   https://abc.com/.git/config
   ```

   Multiple paths can also be specified in one request which will be requested for the target.

3. **Headers** can also be specified to be sent along with the requests. Headers are placed in form of Key-Value pairs. An example header configuration is - 

   ```yaml
   # headers contains the headers for the request
   headers:
    # Custom user-agent header
    User-Agent: Some-Random-User-Agent
    # Custom request origin
    Origin: https://google.com
   ```

4. **Body** specifies a body to be sent along with the request. For instance - 

   ```yaml
   # body is a string sent along with the request
   body: "{\"some random json\"}"
   ```

5. **Matchers** are the core of nuclei. They are what make the tool so powerful. Multiple type of combinations and checks can be added to ensure that the results you get are free from false positives.

   Multiple matchers can be specified in a request. There are basically 4 types of matchers - 

   | Matcher Type | Part Matched               |
   | ------------ | -------------------------- |
   | status       | Status Code of Response    |
   | size         | Content Length of Response |
   | word         | Response body or headers   |
   | regex        | Response body or headers   |

   To match status codes for responses, you can use the following syntax.

   ```yaml
   matcher:
    # match the status codes
    - type: status
      # some status codes we want to match
      status: 
       - 200
       - 302
   ```

   To match size, similar structure can be followed. If the status code of response from the site matches any single one specified in the matcher, the request is marked as successful.

   **Word** and **Regex **matchers can be further configured depending on the needs of the users. 

   Multiple words and regexes can be specified in a single matcher and can be configured with different conditions like **AND** and **OR**. 

   1. **AND** - Using AND conditions allows matching of all the words from the list of words for the matcher. Only then will the request be marked as successful when all the words have been matched.
   2. **OR** - Using OR conditions allows matching of a single word from the list of matcher. The request will be marked as successful when even one of the word is matched for the matcher.

   Multiple parts of the response can also be matched for the request. 

   | Part   | Matched Part                         |
   | ------ | ------------------------------------ |
   | body   | Body of the response                 |
   | header | Header of the response               |
   | all    | Both body and header of the response |

   Example matchers for response body using AND condition - 

   ```yaml
   matcher:
     # match the body word
     - type: word
      # some words we want to match
      words: 
        - "[core]"
        - "[config]"
      # both words must be found in the response body
      condition: and
      #  we want to match request body (default)
      part: body
   ```

Similarly, matchers can be written to match anything that you want to find in the response body allowing unlimited creativity and extensibility.

The final template file for the `.git/config` file mentioned above is as follows - 

```yaml
id: git-config

info:
  name: Git Config File
  author: Ice3man
  severity: medium

requests:
  - method: GET
    path:
      - "{{BaseURL}}/.git/config"
    matchers:
      - type: word
        words:
          - "[core]"
```

