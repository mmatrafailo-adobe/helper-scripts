package main

import (
	"fmt"
	"github.com/google/uuid"
	"io/ioutil"
	"log"
	"os"
	"os/exec"
	"path/filepath"
	"regexp"
	"strings"
	"sync"
	"time"
)

var wg sync.WaitGroup

func sum(a int, b int, c chan int) {
	//defer wg.Done()
	c <- a + b // send sum to c
	c <- a - b // send sum to c
	close(c)
}

func runCommandWithChannel(command string, dir string, msg chan string) {
	commandObject := exec.Command("bash", "-c", command)
	commandObject.Dir = dir
	out, err := commandObject.CombinedOutput()
	msg <- string(out)
	if err != nil {
		msg <- "Failed to execute command " + command + " Error: " + err.Error()
	}
}

func createFolderAndInitProject(dir string, projectName string, jiraTicketUrl string, msg chan string) {
	runCommandWithChannel("warden env-init "+projectName+" magento2", dir, msg)
	runCommandWithChannel("warden sign-certificate "+projectName+".test", dir, msg)

	if jiraTicketUrl != "" {
		f, err := os.OpenFile(dir+"/.env", os.O_APPEND|os.O_WRONLY, 0644)
		defer f.Close()
		if err == nil {
			_, err := f.WriteString("JIRA_TICKET_URL=" + jiraTicketUrl)
			if err != nil {
				msg <- "Error writing " + dir + "/.env file: " + err.Error()
			}
		} else {
			msg <- "error with writing .env file: " + err.Error()
		}
	}

	makeIdeaFolder(dir, projectName)

	//bytesRead, err := ioutil.ReadFile(src)

	//runCommandWithChannel("warden env up", dir, msg)

	ex, err := os.Executable()
	if err != nil {
		panic(err)
	}
	binInstallationDir := filepath.Dir(ex)
	bytesRead, err := ioutil.ReadFile(binInstallationDir + "/../bin/replace-config.php")
	if err != nil {
		log.Fatal(err)
	}
	err = ioutil.WriteFile(dir+"/replace-config.php", bytesRead, 0644)
	msg <- "config file moved successfully"

	//fmt.Println("path of go bin: " + binInstallationDir)

	close(msg)
}

func downloadCodeDumps(dir string, sshUrl string, msg chan string) {

	runCommandWithChannel("cloud-teleport  "+sshUrl+" dump -d code", dir, msg)
	close(msg)
}

func makeIdeaFolder(dir string, projectName string) {

	ideaFolderPath := dir + "/.idea"
	os.Mkdir(ideaFolderPath, 0777)

	dataSources := `<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
  <component name="DataSourceManagerImpl" format="xml" multifile-model="true">
    <data-source source="LOCAL" name="magento@${PROJECT_NAME}_db_1" uuid="${UUID}">
      <driver-ref>mariadb</driver-ref>
      <synchronize>true</synchronize>
      <user-name>magento</user-name>
      <jdbc-driver>org.mariadb.jdbc.Driver</jdbc-driver>
      <jdbc-url>jdbc:mariadb://${PROJECT_NAME}_db_1:3306/magento</jdbc-url>
      <working-dir>$ProjectFileDir$</working-dir>
      <ssh-properties>
              <enabled>true</enabled>
              <ssh-config-id>2a7205b3-f1f8-4185-9dd7-5e27e48f11f1</ssh-config-id>
            </ssh-properties>
    </data-source>
  </component>
</project>`
	dataSources = strings.Replace(dataSources, "${PROJECT_NAME}", projectName, -1)
	dataSources = strings.Replace(dataSources, "${UUID}", uuid.NewString(), -1)

	ioutil.WriteFile(ideaFolderPath+"/dataSources.xml", []byte(dataSources), 0664)

	workspace := `<?xml version="1.0" encoding="UTF-8"?>
<project version="4">
  <component name="PhpServers">
      <servers>
        <server host="${PROJECT_NAME}-docker" id="${UUID}" name="${PROJECT_NAME}-docker" use_path_mappings="true">
          <path_mappings>
            <mapping local-root="$PROJECT_DIR$" remote-root="/var/www/html" />
          </path_mappings>
        </server>
      </servers>
    </component>
</project>`

	workspace = strings.Replace(workspace, "${PROJECT_NAME}", projectName, -1)
	workspace = strings.Replace(workspace, "${UUID}", uuid.NewString(), -1)

	ioutil.WriteFile(ideaFolderPath+"/workspace.xml", []byte(dataSources), 0664)
}

func getProjectNameFromJIRAURL(jiraUrl string) string {
	// take last path part jira.com/browse/MDVA-1234?fwefewf=ewf => MDVA-1234
	parts := strings.Split(jiraUrl, "/")
	projectNamePart := parts[len(parts)-1]
	if strings.Contains(projectNamePart, "?") {
		parts = strings.Split(projectNamePart, "?")
		projectNamePart = parts[0]
	}

	// remove all except letters and numbers
	reg, err := regexp.Compile("[^a-zA-Z0-9]+")
	if err != nil {
		log.Fatal(err)
	}
	projectNamePart = reg.ReplaceAllString(projectNamePart, "")

	return strings.ToLower(projectNamePart)
}

func main() {
	start := time.Now()

	currentDir, err := os.Getwd()
	if err != nil {
		panic(err)
	}

	fmt.Print("Enter JIRA url or folder name (skip if it is a current dir): ")
	var jiraTicketUrl string
	fmt.Scan(&jiraTicketUrl)

	var projectName string
	var projectDir string

	if jiraTicketUrl == "" {
		projectName = filepath.Base(currentDir)
		projectDir = currentDir
	} else if strings.Contains(projectName, "http") || strings.Contains(projectName, "/") {
		projectName = getProjectNameFromJIRAURL(jiraTicketUrl)
		projectDir = currentDir + "/" + projectName
	} else {
		projectName = getProjectNameFromJIRAURL(jiraTicketUrl)
		projectDir = currentDir + "/" + projectName
		jiraTicketUrl = ""
	}

	os.Mkdir(projectDir, 0777)

	messages := make(chan string)
	go createFolderAndInitProject(projectDir, projectName, jiraTicketUrl, messages)

	fmt.Print("Enter PROJECT ID (\"N\" to skip): ")
	var projectId string
	fmt.Scan(&projectId)

	dumpMessagesChan := make(chan string)
	if projectId != "N" {
		fmt.Println("Project id is " + projectId)

		listEnvsString := runCommand(" magento-cloud environment:list --project " + projectId + " --pipe")
		fmt.Println(listEnvsString)

		var envId string
		fmt.Print("Enter env id: ")
		fmt.Scan(&envId)

		// ucxdbrjol65si
		sshPath := runCommand("magento-cloud env:ssh --project " + projectId + " --environment " + envId + " --pipe")

		go downloadCodeDumps(projectDir, sshPath, dumpMessagesChan)

		commandObject := exec.Command("bash", "-c", "cloud-teleport "+sshPath+" dump -d db")
		commandObject.Dir = projectDir
		commandObject.Stdin = os.Stdin
		commandObject.Stdout = os.Stdout
		commandObject.Stderr = os.Stderr
		err := commandObject.Run()

		if err != nil {
			fmt.Println("Error of running db dump command: " + err.Error())
		}

		fmt.Println(sshPath)
	} else {
		fmt.Println("Project ID is empty, Skipping downloading the dumps")
		close(dumpMessagesChan)
	}

	for message := range messages {
		fmt.Println(message)
	}

	for dumpMessage := range dumpMessagesChan {
		fmt.Println(dumpMessage)
	}

	//c := make(chan int)
	////wg.Add(1)
	//go sum(20, 22, c)
	////wg.Add(1)
	////go sum(21, 24, c)
	////wg.Wait()
	//
	//for i := range c {
	//
	//	fmt.Println(i)
	//}
	////x := <-c // receive from c
	////
	////fmt.Println(x)

	elapsed := time.Since(start)
	fmt.Printf("Time in runtime %s\n", elapsed)
}

func runCommand(command string) (output string) {
	out, err := exec.Command("bash", "-c", command).CombinedOutput()
	if err != nil {
		//fmt.Println(fmt.Sprintf("Failed to execute command: %s \n error is: ", command, err))
		//panic(err)
	}

	return string(out)
}
