# Template Contribution Guidelines

This documentation contains a set of guidelines to help you during the contribution process. 
We are happy to welcome all the contributions from anyone willing to **improve/add** new **templates** to this project. 
Thank you for helping out and remember, **no contribution is too small.**

# Submitting Nuclei Templates 👩‍💻👨‍💻

Below you will find the process and workflow used to review and merge your changes.

## Step 1 : Find existing templates

- Take a look at the [Existing Templates](https://github.com/projectdiscovery/nuclei-templates) before creating new one.
- Take a look at Existing Templates in [GitHub Issues](https://github.com/projectdiscovery/nuclei-templates/issues) and [Pull Request](https://github.com/projectdiscovery/nuclei-templates/pulls) section to avoid duplicate work.
- Take a look at [Templates](https://nuclei.projectdiscovery.io/templating-guide/) and [Matchers](https://github.com/projectdiscovery/nuclei-templates/wiki/Unique-Template-Matchers) Guideline for creating new template.

## Step 2 : Fork the Project

- Fork this Repository. This will create a Local Copy of this Repository on your Github Profile. Keep a reference to the original project in `upstream` remote.

<img width="928" alt="template-fork" src="https://user-images.githubusercontent.com/8293321/124467966-2afde200-ddb6-11eb-835f-8f8fc2fabedb.png">

```sh
git clone https://github.com/<your-username>/nuclei-templates
cd nuclei-templates
git remote add upstream https://github.com/projectdiscovery/nuclei-templates
```

- If you have already forked the project, update your copy before working.

```sh
git remote update
git checkout master
git rebase upstream/master
```

## Step 3 : Create your Template Branch

Create a new branch. Use its name to identify the issue your addressing.

```sh
# It will create a new branch with name template_branch_name and switch to that branch
git checkout -b template_branch_name
```

## Step 4 : Create Template and Commit
- Create your template.
- Add all the files/folders needed.
- After you've made changes or completed template creation, add changes to the branch you've just created by:

```sh
# To add all new files to branch template_branch_name
git add .
```

- To commit, give a descriptive message for the convenience of the reviewer by:

```sh
# This message get associated with all files you have changed
git commit -m "Added/Fixed/Updated XXX Template"
```

**NOTE**:

- A Pull Request should have only one unique template to make it simple for review.
- Multiple templates for same technology can be grouped into single Pull Request. 


## Step 5 : Push Your Changes

- Now you are ready to push your template to the remote (forked) repository.
- When your work is ready and complies with the project conventions, upload your changes to your fork:

```sh
# To push your work to your remote repository
git push -u origin template_branch_name
```

## Step 6 : Pull Request

- Fire up your favorite browser, navigate to your GitHub repository, then click on the New pull request button within the Pull requests tab. Provide a meaningful name and description to your pull request, that describes the purpose of the template.
- Voila! Your Pull Request has been submitted. It will be reviewed and merged by the moderators, if it complies with project standards, otherwise a feedback will be provided.🥳

## Need more help?🤔

You can refer to the following articles of Git and GitHub basics. In case you are stuck, feel free to contact the Project Mentors and Community by joining [PD Community](https://discord.gg/projectdiscovery) Discord server.

- [Forking a Repo](https://help.github.com/en/github/getting-started-with-github/fork-a-repo)
- [Cloning a Repo](https://help.github.com/en/desktop/contributing-to-projects/creating-an-issue-or-pull-request)
- [How to create a Pull Request](https://opensource.com/article/19/7/create-pull-request-github)
- [Getting started with Git and GitHub](https://towardsdatascience.com/getting-started-with-git-and-github-6fcd0f2d4ac6)
- [Learn GitHub from Scratch](https://lab.github.com/githubtraining/introduction-to-github)


## Tip from us😇

- **Nuclei** outcomes are only as excellent as **template matchers💡**
- Declare at least two matchers to reduce false positive
- Avoid matching words reflected in the URL to reduce false positive
- Avoid short word that could be encountered anywhere
