/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package ru.cherezweb.hosting.srv.starter;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.ServletException;

import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.Writer;
import java.lang.management.ManagementFactory;
import java.math.BigInteger;
import java.net.URLEncoder;
import java.security.MessageDigest;
import java.util.Properties;

import org.eclipse.jetty.server.Server;
import org.eclipse.jetty.server.Request;
import org.eclipse.jetty.server.handler.AbstractHandler;
import org.json.JSONObject;

/**
 *
 * @author gda
 */
public class Starter extends AbstractHandler {

    @Override
    public void handle(String target,
            Request baseRequest,
            HttpServletRequest request,
            HttpServletResponse response)
            throws IOException, ServletException {
        String resultString;
        try {
            String clientIP = request.getRemoteAddr();
            if (clientIP.compareTo(getParameter("app.main_server_ip")) != 0) {
                throw new Exception("Доступ с IP \"" + clientIP + "\" запрещен.");
            }

            JSONObject dataJSONObject = new JSONObject(request.getParameter("data"));
            String accessKey = dataJSONObject.getString("accessKey");
            String accessKeyHash = getParameter("app.access_key_hash");
            if (accessKeyHash.compareTo(md5(accessKey)) != 0) {
                throw new Exception("Неправильный ключ безопасности.");
            }
            dataJSONObject.remove("accessKey");
            resultString = this.executeCommand(
                new String[]{
                    "php", "-f", "../task_exec.php",
                    URLEncoder.encode(
                        dataJSONObject.toString(),
                        "UTF-8"
                    )
                }
            );
        } catch (Exception e) {
            JSONObject resultJSONObject = new JSONObject();
            resultJSONObject.put("result", JSONObject.NULL);
            
            StringBuilder errorDescription = new StringBuilder(e.toString() + ":");
            for (StackTraceElement element : e.getStackTrace()){
                errorDescription.append(element).append("\n");
            }
            resultJSONObject.put("error", errorDescription.toString());
            resultString = resultJSONObject.toString();
        }

        response.setContentType("text/html;charset=utf-8");
        response.setStatus(HttpServletResponse.SC_OK);
        baseRequest.setHandled(true);
        response.getWriter().println(resultString);
    }
    
    public static String md5(String input) throws Exception{
        String result = input;
        if(input != null) {
            MessageDigest md = MessageDigest.getInstance("MD5"); //or "SHA-1"
            md.update(input.getBytes());
            BigInteger hash = new BigInteger(1, md.digest());
            result = hash.toString(16);
            while(result.length() < 32) { //40 for SHA-1
                result = "0" + result;
            }
        }
        return result;
    }
    
    private String getParameter(String name) throws Exception {
        Properties parameters = new Properties();
        parameters.load(new FileInputStream("../data/parameters.ini"));
        String value = parameters.getProperty(name);
        if (value == null) {
            throw new Exception("Параметр \"" + name + "\" не задан в фалей параметров.");
        }
        if (value.startsWith("\"")) {
            value = value.substring(1, value.length() - 1);
        }
        return value;
    }

    private String executeCommand(String[] command) throws Exception {

        StringBuilder output = new StringBuilder();
        StringBuilder error = new StringBuilder();

        Process p;
        p = Runtime.getRuntime().exec(command);
        p.waitFor();

        BufferedReader reader;
        String line;

        reader = new BufferedReader(new InputStreamReader(p.getInputStream()));
        while ((line = reader.readLine()) != null) {
            output.append(line).append("\n");
        }

        reader = new BufferedReader(new InputStreamReader(p.getErrorStream()));
        while ((line = reader.readLine()) != null) {
            error.append(line).append("\n");
        }

        if (p.exitValue() != 0) {
            throw new Exception("Ошибка (" + String.valueOf(p.exitValue()) + ") при выполнении shell команды (" + command + "):\nresult: " + output + "\nerror: " + error + "\n");
        }

        return output.toString().trim();

    }

    public static void main(String[] args) throws Exception {
        Server server = new Server(81);
        server.setHandler(new Starter());
        try {
            server.start();
            try (Writer out = new BufferedWriter(
                new OutputStreamWriter(
                    new FileOutputStream("pid.txt"),
                    "UTF-8"
                )
            )) {
                out.write(ManagementFactory.getRuntimeMXBean().getName());
            }
            server.join();
        } catch (Exception e) {
            System.out.println(e);
            Runtime.getRuntime().exit(1);
        }
    }

}
