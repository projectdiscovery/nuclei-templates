package main

import (
	"encoding/json"
	"fmt"
	"io/ioutil"
	"os"
	"path/filepath"
	"strings"

	"gopkg.in/yaml.v3"
)

type Classification struct {
	CVSSScore string `yaml:"cvss-score,omitempty"`
}

type Info struct {
	Name           string         `yaml:"name"`
	Severity       string         `yaml:"severity"`
	Description    string         `yaml:"description"`
	Classification Classification `yaml:"classification,omitempty"`
}

type Data struct {
	ID       string `yaml:"id"`
	Info     Info   `yaml:"info"`
	FilePath string `json:"file_path"`
}

func main() {
	if len(os.Args) != 3 {
		fmt.Println("Usage: go run main.go <directory1[,directory2,...]> <output_file>")
		os.Exit(1)
	}

	input := os.Args[1]
	outputFile := os.Args[2]
	var directories []string

	// Check if the input contains a comma
	if strings.Contains(input, ",") {
		directories = strings.Split(input, ",")
	} else {
		directories = []string{input}
	}

	var data []Data

	for _, directory := range directories {
		fmt.Println("Generating data for", directory)

		err := filepath.Walk(directory, func(path string, info os.FileInfo, err error) error {
			if err != nil {
				fmt.Printf("Error accessing path %s: %v\n", path, err)
				return err
			}
			if strings.HasSuffix(path, ".yaml") || strings.HasSuffix(path, ".yml") {
				yamlFile, err := ioutil.ReadFile(path)
				if err != nil {
					fmt.Printf("Error reading YAML file %s: %v\n", path, err)
					return err
				}

				var d Data
				err = yaml.Unmarshal(yamlFile, &d)
				if err != nil {
					fmt.Printf("Error unmarshalling YAML file %s: %v\n", path, err)
					return err
				}
				if d.Info.Classification.CVSSScore == "" {
					d.Info.Classification.CVSSScore = "N/A"
				}
				if d.Info.Classification == (Classification{}) {
					d.Info.Classification.CVSSScore = "N/A"
				}
				fpath := strings.Replace(path, "/home/runner/work/nuclei-templates/nuclei-templates/", "", 1)
				d.FilePath = fpath

				data = append(data, d)
			}
			return nil
		})

		if err != nil {
			fmt.Printf("Error reading directory: %v\n", err)
			os.Exit(1)
		}
	}

	var jsonData []byte
	for _, d := range data {
		temp, err := json.Marshal(d)
		if err != nil {
			fmt.Printf("Error marshalling JSON: %v\n", err)
			os.Exit(1)
		}
		jsonData = append(jsonData, temp...)
		jsonData = append(jsonData, byte('\n'))
	}
	err := ioutil.WriteFile(outputFile, jsonData, 0644)
	if err != nil {
		fmt.Printf("Error writing JSON data to file: %v\n", err)
		os.Exit(1)
	}

	fmt.Println("JSON data written to", outputFile)
}
