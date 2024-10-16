# Nuclei Contribution Guide
Templates are the core of the [nuclei scanner](https://github.com/projectdiscovery/nuclei) which powers the actual scanning engine. The Nuclei Templates repository stores and houses various templates for a variety of protocols, including TCP, DNS, HTTP, SSL, File, Whois, Websocket, Headless etc. for the scanner provided by our team, as well as contributed by the community.

We have over **9000+** templates contributed by **more than 800** security researchers and engineers. We hope that you contribute by sending templates via **pull requests** or [Github issues](https://github.com/projectdiscovery/nuclei-templates/issues/new?assignees=&labels=&template=submit-template.md&title=%5Bnuclei-template%5D+) to grow the list. By contributing, you won't only help the community **❤️** but can also gain experience, increase community and peer recognition, improving your job prospects

This documentation contains a set of guidelines to help you during the contribution process. We are happy to welcome all the contributions from anyone willing to **improve/add** new **templates** to this project. Thank you for helping out and remember, **no contribution is too small.**

## **How Can I Contribute?**

- [Submitting Nuclei Templates](#Submitting-Nuclei-Templates)
- [Reporting False Negative Template](#Reporting-False-Positive-Template)
- [Reporting False Positive Template](#Reporting-False-Positive-Template)
- [Enhancing existing templates](#Enhancing-existing-templates)
- [Reporting Invalid templates](#Reporting-Invalid-templates)
- [Request Template](#Request-Template)
- [Sharing idea / feature for nuclei-templates](#Sharing-idea-/-feature-for-nuclei-templates)

### **Submitting Nuclei Templates**

**Before Submitting an Issue or Pull Request**

- Take a look at the [Existing Templates](https://github.com/projectdiscovery/nuclei-templates) or search for endpoints before creating new one.
- Take a look at Existing Templates in [GitHub Issues](https://github.com/projectdiscovery/nuclei-templates/issues) and [Pull Request](https://github.com/projectdiscovery/nuclei-templates/pulls) section to avoid duplicate work.
- Take a look at [Templates](https://nuclei.projectdiscovery.io/templating-guide/) and [Matchers](https://github.com/projectdiscovery/nuclei-templates/wiki/Unique-Template-Matchers) Guideline for creating new template.

Along with the P.O.C following are the required fields in the info section for submitting new template.

1. `id`: It should be short ideally max of 3-4 words. For example `grafana-unauth-rce`
2. `name` : The name should be short in this format `<Vendor> <Product> <Version> - <Vulnerability>` 
3. `author`: It can be your github/twitter username or alias. You can also create a PR to add more details associated with the author name here (https://github.com/projectdiscovery/nuclei-templates/blob/main/contributors.json)
4. `severity` : Based on the CVSS score but can vary based on the exploit and real-world impact
5. `description` : Short description of the vulnerability
6. `reference` : Please provide the reference to the POC, setup guide or the product details to help the team verify the template.

**Do’s**

- If you have verified the template, mark it as `verified: true` under metadata field and share the debug data using `-debug` flag after redacting the vulnerable server information in the PR
- Make sure to add more than one matcher to prevent false positive results. Avoid short word that could be encountered anywhere
- If possible submit the vulnerable environment based on docker-compose. For example: https://github.com/vulhub/vulhub.
- We only accept templates with complete P.O.Cs instead of just detection based on version

**Don’t**

- Don’t not share any real world target on the PR. If you have setup an vulnerable environment please share it privately on Discord with the team to easily validate the template.
- Avoid submitting templates with weak matchers. For example: Adding GET/POST data as the matchers in the template, as it can result in false positive results on few hosts
- Don’t make unnecessary changes to the existing templates like adding more requests to the templates when the existing requests or paths are good enough to verify that the bug exists
- Try to keep the requests per template as low as possible

**Best Practices**

- Make sure to add the template in the appropriate directory.
- Add part with the matchers. For example if the matcher is in response body add `part:` body
- Use `cmd` variable for RCE templates so that they are unified throughout the repo
- Use `{{username}}` and `{{password}}` variables in all authenticated templates
- Use `{{token}}` variable in all the template that deals with keys or tokens
- If there are more than 1 template for a tech create a separate folder for it
- Don't share any vulnerable URL publicly on Github or Discord channel.
- We should only upload a web shell as a last resort to validate the vulnerability, and if we do upload a file, make sure the file name is random(`{{randstr}}`)
- Do not include code templates for exploits that can be written using HTTP or JavaScript. We avoid adding additional exploit code to the project unless there is an exception.

### **Submitting a PR**

**Fork the Project**

- This will create a Local Copy of this Repository on your Github Profile. Keep a reference to the original project in `upstream` remote.
<img width="928" alt="template-fork" src="https://user-images.githubusercontent.com/8293321/124467966-2afde200-ddb6-11eb-835f-8f8fc2fabedb.png">

```jsx
git clone https://github.com/<your-username>/nuclei-templates
cd nuclei-templates
git remote add upstream https://github.com/projectdiscovery/nuclei-templates
```

- If you have already forked the project, update your copy before working.

```jsx
git remote update
git checkout main
git rebase upstream/main
```

**Create your Template Branch**

- Create a new branch. Use its name to identify the issue your addressing.

```jsx
# It will create a new branch with name template_branch_name and switch to that branch
git checkout -b template_branch_name
```

**Create Template and Commit**

- Create your template.
- Add all the files/folders needed.
- After you've made changes or completed template creation, add changes to the branch you've just created by:

```jsx
# To add all new files to branch template_branch_name
git add .
```

- To commit, give a descriptive message for the convenience of the reviewer by:

```jsx
# This message get associated with all files you have changed
git commit -m "Added/Fixed/Updated XXX Template"
```

**NOTE**:

- Try to add only one templates per Pull Request as it will make it simple for us to review and the PR will not be blocked because of one of the templates
- Multiple templates for same technology can be grouped into single Pull Request.

**Push Your Changes**

- Now you are ready to push your template to the remote (forked) repository.
- When your work is ready and complies with the project conventions, upload your changes to your fork:

```jsx
# To push your work to your remote repository
git push -u origin template_branch_name
```

**Pull Request**

- Fire up your favorite browser, navigate to your GitHub repository, then click on the New pull request button within the Pull requests tab. Provide a meaningful name and description to your pull request, that describes the purpose of the template.
- Voila! Your Pull Request has been submitted. It will be reviewed and merged by the moderators, if it complies with project standards, otherwise a feedback will be provided.🥳

### Reporting [False Negative Template](https://github.com/projectdiscovery/nuclei-templates/issues/new?template=false-negative.yml)

You can contribute to the project by creating issue/PR for templates which are missing valid/expected result.

- Share you nuclei version and the path of the template
- Share the `-debug` data for the host where the template is not matching the vulnerable target
- If possible share the improved or valid matchers, references and the information to setup vulnerable environment.

> Note: If host information can not be shared publicly, please reach out to us on discord server in DM.
> 

**Creating a [False negative issue](https://github.com/projectdiscovery/nuclei-templates/issues/new?template=false-negative.yml) or Submit a PR**

- Click on the Issues Tab and then click on `new issue.`
- Click on `get started` in front of **`False Negative`**

### Reporting [False Positive Template](https://github.com/projectdiscovery/nuclei-templates/issues/new?template=false-positive.yml)

You can contribute to the project by creating issue/PR for templates which are producing invalid/unexpected result.

- Share you nuclei version and the path of the template
- Share the `-debug` data and if possible the host where the template is matching the non-vulnerable target and producing invalid/unexpected result.
- If possible share the improved or valid matchers and reference to the vulnerability.

**Creating a [False positive issue](https://github.com/projectdiscovery/nuclei-templates/issues/new?template=false-positive.yml) or Submit a PR**

- Click on the Issues Tab and then click on `new issue.`
- Click on `get started` in front of **`False Positive`**

### Enhancing existing templates

You can contribute to the project by creating issue/PR for enhancement of nuclei-templates repository which includes changing directory structure, adding new categories or fields to the templates etc

Share the reason or requirement for the enhancement and how can it improve the overall quality of the template(s).

**Creating a Issue for Suggesting Enhancements or Submit a PR**

- Click on the Issues Tab and then click on `new issue`
- Click on `get started` in front of `Enhancement request`

### Reporting Invalid templates

If you have encountered some invalid template or any template in the repo resulting in unexpected errors then please report it as invalid template. Make sure to provide the following info:

- Share you nuclei version and the path of the template
- Share the screenshot with the error and the  `-verbose` output and if applicable also provide the debug data using `-debug` flag
- If this is specific to one environment and the bug don’t exist on the other setup please provide the OS and details your setup

**Creating a Issue for reporting Invalid template**

- Click on the Issues Tab and then click on `new issue`
- Click on `get started` in front of `Report Issue`

### Request Template

If you have a reference to the POC of any vulnerbaility or new CVE. You can create an issue to template the template and the team will create one. Make sure to provide the following info:

- Reference to the vulnerability with the complete P.O.C
- If possible share the vulnerable docker image or steps to setup vulnerable environment

> Note: If have setup the vulnerable environment. You can share the host with the team on discord server in DM.
> 

**Creating a Issue for requesting nuclei template**

- Click on the Issues Tab and then click on `new issue`
- Click on `get started` in front of `Request Template`

### Sharing idea / feature for nuclei-templates

If you have any ideas or want to request a feature for nuclei-templates you can do so by creating a new discussion.

**Creating a Discussion for sharing idea / feature**

- Click on the Issues Tab and then click on `new issue`
- Click on `open` in front of `Share idea / feature to discuss for nuclei-templates`
