---
name: False Positive
about: 'Create an issue if you found false positive results. '
title: "[false-positive] template-name "
labels: 'false-positive'
assignees: ''

---

**Nuclei version**

```
nuclei -version 
```

**Nuclei template version**

```
cat ~/.nuclei-config.json
```

**Template ID**

Please submit the ID template producing false-positive results. 

**Commands to Reproduce**

```
nuclei -t template_id -target ?
```
